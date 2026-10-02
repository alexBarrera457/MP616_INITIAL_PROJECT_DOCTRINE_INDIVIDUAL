<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;
use UnexpectedValueException;

final class NurseJsonDataProvider
{
    private readonly string $filePath;

    public function __construct(KernelInterface $kernel)
    {
        $this->filePath = $kernel->getProjectDir().'/data/nurse_credentials.json';
    }

    /**
     * @return list<array{user: string, password: string, credentials: list<array{
     *     licenseNumber: string,
     *     certification: string,
     *     issuingBody: string,
     *     expirationDate: string
     * }>}>
     */
    public function getNurses(): array
    {
        $json = file_get_contents($this->filePath);
        if ($json === false) {
            throw new \RuntimeException(sprintf('Could not read nurse data file "%s".', $this->filePath));
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new UnexpectedValueException('The nurse data file contains invalid JSON.', previous: $exception);
        }

        if (!is_array($data) || ($data['schemaVersion'] ?? null) !== 1
            || !isset($data['nurses']) || !is_array($data['nurses']) || !array_is_list($data['nurses'])
        ) {
            throw new UnexpectedValueException('The nurse data file has an invalid structure.');
        }

        $nurses = [];
        foreach ($data['nurses'] as $nurse) {
            if (!is_array($nurse)
                || !isset($nurse['user']) || !is_string($nurse['user'])
                || !isset($nurse['password']) || !is_string($nurse['password'])
                || !isset($nurse['credentials']) || !is_array($nurse['credentials'])
                || !array_is_list($nurse['credentials'])
            ) {
                throw new UnexpectedValueException('The nurse data file contains an invalid nurse entry.');
            }

            $credentials = [];
            foreach ($nurse['credentials'] as $credential) {
                if (!is_array($credential)
                    || !isset(
                        $credential['licenseNumber'],
                        $credential['certification'],
                        $credential['issuingBody'],
                        $credential['expirationDate']
                    )
                    || !is_string($credential['licenseNumber'])
                    || !is_string($credential['certification'])
                    || !is_string($credential['issuingBody'])
                    || !is_string($credential['expirationDate'])
                ) {
                    throw new UnexpectedValueException('The nurse data file contains an invalid credential entry.');
                }

                $credentials[] = [
                    'licenseNumber' => $credential['licenseNumber'],
                    'certification' => $credential['certification'],
                    'issuingBody' => $credential['issuingBody'],
                    'expirationDate' => $credential['expirationDate'],
                ];
            }

            $nurses[] = [
                'user' => $nurse['user'],
                'password' => $nurse['password'],
                'credentials' => $credentials,
            ];
        }

        return $nurses;
    }

    /**
     * @return list<array{user: string, credentials: list<array{
     *     licenseNumber: string,
     *     certification: string,
     *     issuingBody: string,
     *     expirationDate: string
     * }>} >
     */
    public function findAll(): array
    {
        return array_map(
            static fn (array $nurse): array => [
                'user' => $nurse['user'],
                'credentials' => $nurse['credentials'],
            ],
            $this->getNurses()
        );
    }

    /**
     * @return array{user: string, credentials: list<array{
     *     licenseNumber: string,
     *     certification: string,
     *     issuingBody: string,
     *     expirationDate: string
     * }>}|null
     */
    public function findByName(string $name): ?array
    {
        foreach ($this->findAll() as $nurse) {
            if (strcasecmp($nurse['user'], $name) === 0) {
                return $nurse;
            }
        }

        return null;
    }

    /**
     * @return array{user: string, credentials: list<array{
     *     licenseNumber: string,
     *     certification: string,
     *     issuingBody: string,
     *     expirationDate: string
     * }>}|null
     */
    public function authenticate(string $user, string $password): ?array
    {
        foreach ($this->getNurses() as $nurse) {
            if ($nurse['user'] === $user && hash_equals($nurse['password'], $password)) {
                unset($nurse['password']);

                return $nurse;
            }
        }

        return null;
    }
}
