<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use InvalidArgumentException;

final class NurseCredentialImporter
{
    private const MAX_FILE_SIZE = 5_242_880;
    private const MAX_CREDENTIALS = 10_000;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function importFile(string $path): int
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException('The JSON file does not exist or is not readable.');
        }

        $size = filesize($path);
        if ($size === false || $size > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('The JSON file exceeds the 5 MiB size limit.');
        }

        $json = file_get_contents($path);
        if ($json === false) {
            throw new InvalidArgumentException('The JSON file could not be read.');
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('The file does not contain valid JSON.', previous: $exception);
        }

        $credentials = $this->validateData($data);

        return $this->connection->transactional(function (Connection $connection) use ($credentials): int {
            foreach ($credentials as $credential) {
                $exists = $connection->fetchOne(
                    'SELECT 1 FROM nurse_credentials
                     WHERE nurse_user = :nurse_user
                       AND license_number = :license_number
                       AND certification = :certification',
                    [
                        'nurse_user' => $credential['nurseUser'],
                        'license_number' => $credential['licenseNumber'],
                        'certification' => $credential['certification'],
                    ]
                );

                if ($exists !== false) {
                    throw new InvalidArgumentException(sprintf(
                        'Credential already exists for nurse "%s", license "%s", certification "%s".',
                        $credential['nurseUser'],
                        $credential['licenseNumber'],
                        $credential['certification']
                    ));
                }

                $connection->insert('nurse_credentials', [
                    'nurse_user' => $credential['nurseUser'],
                    'license_number' => $credential['licenseNumber'],
                    'certification' => $credential['certification'],
                    'issuing_body' => $credential['issuingBody'],
                    'issue_date' => $credential['issueDate'],
                    'expiration_date' => $credential['expirationDate'],
                ]);
            }

            return count($credentials);
        });
    }

    /**
     * @return list<array{
     *     nurseUser: string,
     *     licenseNumber: string,
     *     certification: string,
     *     issuingBody: string,
     *     issueDate: string,
     *     expirationDate: string
     * }>
     */
    private function validateData(mixed $data): array
    {
        if (!is_array($data) || array_is_list($data)
            || array_diff(array_keys($data), ['schemaVersion', 'nurses']) !== []
            || ($data['schemaVersion'] ?? null) !== 1
            || !isset($data['nurses']) || !is_array($data['nurses']) || !array_is_list($data['nurses'])
            || $data['nurses'] === []
        ) {
            throw new InvalidArgumentException('Expected schemaVersion 1 and a non-empty nurses array.');
        }

        $credentials = [];
        $seen = [];
        $today = new \DateTimeImmutable('today');

        foreach ($data['nurses'] as $nurseIndex => $nurse) {
            if (!is_array($nurse) || array_is_list($nurse)
                || array_diff(array_keys($nurse), ['user', 'password', 'credentials']) !== []
                || !isset($nurse['user']) || !is_string($nurse['user'])
                || !preg_match('/^[A-Za-z0-9._-]{1,64}$/D', $nurse['user'])
                || !isset($nurse['password']) || !is_string($nurse['password'])
                || strlen($nurse['password']) < 4 || strlen($nurse['password']) > 128
                || !isset($nurse['credentials']) || !is_array($nurse['credentials'])
                || !array_is_list($nurse['credentials']) || $nurse['credentials'] === []
            ) {
                throw new InvalidArgumentException(sprintf('Nurse at index %d is missing a valid user or credentials.', $nurseIndex));
            }

            foreach ($nurse['credentials'] as $credentialIndex => $credential) {
                if (count($credentials) >= self::MAX_CREDENTIALS) {
                    throw new InvalidArgumentException('The JSON file exceeds the 10,000 credential limit.');
                }

                if (!is_array($credential) || array_is_list($credential)
                    || array_diff(array_keys($credential), [
                        'licenseNumber',
                        'certification',
                        'issuingBody',
                        'issueDate',
                        'expirationDate',
                    ]) !== []
                ) {
                    throw new InvalidArgumentException(sprintf(
                        'Credential at nurses[%d].credentials[%d] has an invalid structure.',
                        $nurseIndex,
                        $credentialIndex
                    ));
                }

                $licenseNumber = $credential['licenseNumber'] ?? null;
                $certification = $credential['certification'] ?? null;
                $issuingBody = $credential['issuingBody'] ?? null;

                if (!is_string($licenseNumber) || !preg_match('/^[A-Z0-9][A-Z0-9-]{4,29}$/D', $licenseNumber)) {
                    throw new InvalidArgumentException(sprintf(
                        'Credential at nurses[%d].credentials[%d] has an invalid license number.',
                        $nurseIndex,
                        $credentialIndex
                    ));
                }

                $certificationLength = is_string($certification)
                    ? preg_match_all('/./us', trim($certification), $matches)
                    : false;

                if (!is_string($certification) || trim($certification) === ''
                    || $certificationLength === false || $certificationLength > 150
                    || preg_match('/\p{C}/u', trim($certification)) === 1
                ) {
                    throw new InvalidArgumentException(sprintf(
                        'Credential at nurses[%d].credentials[%d] has missing or invalid certification information.',
                        $nurseIndex,
                        $credentialIndex
                    ));
                }

                if (!is_string($issuingBody) || !preg_match(
                    "/^[\\p{L}\\p{N}][\\p{L}\\p{N} .,'&()\\/-]{1,148}[\\p{L}\\p{N})]$/uD",
                    trim($issuingBody)
                )) {
                    throw new InvalidArgumentException(sprintf(
                        'Credential at nurses[%d].credentials[%d] has an invalid issuing body.',
                        $nurseIndex,
                        $credentialIndex
                    ));
                }

                $issueDate = $this->parseDate($credential['issueDate'] ?? null, 'issueDate', $nurseIndex, $credentialIndex);
                $expirationDate = $this->parseDate(
                    $credential['expirationDate'] ?? null,
                    'expirationDate',
                    $nurseIndex,
                    $credentialIndex
                );

                if ($issueDate > $expirationDate || $expirationDate < $today) {
                    throw new InvalidArgumentException(sprintf(
                        'Credential at nurses[%d].credentials[%d] has an invalid or expired date range.',
                        $nurseIndex,
                        $credentialIndex
                    ));
                }

                $duplicateKey = $nurse['user']."\0".$licenseNumber."\0".trim($certification);
                if (isset($seen[$duplicateKey])) {
                    throw new InvalidArgumentException(sprintf(
                        'Duplicate credential in JSON for nurse "%s", license "%s", certification "%s".',
                        $nurse['user'],
                        $licenseNumber,
                        trim($certification)
                    ));
                }
                $seen[$duplicateKey] = true;

                $credentials[] = [
                    'nurseUser' => $nurse['user'],
                    'licenseNumber' => $licenseNumber,
                    'certification' => trim($certification),
                    'issuingBody' => trim($issuingBody),
                    'issueDate' => $issueDate->format('Y-m-d'),
                    'expirationDate' => $expirationDate->format('Y-m-d'),
                ];
            }
        }

        if ($credentials === []) {
            throw new InvalidArgumentException('The JSON file must include at least one credential.');
        }

        return $credentials;
    }

    private function parseDate(mixed $value, string $field, int $nurseIndex, int $credentialIndex): \DateTimeImmutable
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                'Credential at nurses[%d].credentials[%d] is missing %s.',
                $nurseIndex,
                $credentialIndex,
                $field
            ));
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException(sprintf(
                'Credential at nurses[%d].credentials[%d] has an invalid %s; expected YYYY-MM-DD.',
                $nurseIndex,
                $credentialIndex,
                $field
            ));
        }

        return $date;
    }
}
