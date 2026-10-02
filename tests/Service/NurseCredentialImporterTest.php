<?php

namespace App\Tests\Service;

use App\Service\NurseCredentialImporter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NurseCredentialImporterTest extends TestCase
{
    private Connection $connection;
    private string $file;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement(
            'CREATE TABLE nurse_credentials (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nurse_user VARCHAR(64) NOT NULL,
                license_number VARCHAR(30) NOT NULL,
                certification VARCHAR(150) NOT NULL,
                issuing_body VARCHAR(150) NOT NULL,
                issue_date DATE NOT NULL,
                expiration_date DATE NOT NULL,
                UNIQUE (nurse_user, license_number, certification)
            )'
        );
        $this->file = tempnam(sys_get_temp_dir(), 'nurse-credentials-');
    }

    protected function tearDown(): void
    {
        $this->connection->close();
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    public function testImportsValidCredentials(): void
    {
        file_put_contents($this->file, json_encode($this->validData(), JSON_THROW_ON_ERROR));

        self::assertSame(1, (new NurseCredentialImporter($this->connection))->importFile($this->file));
        self::assertSame('American Red Cross', $this->connection->fetchOne(
            'SELECT issuing_body FROM nurse_credentials WHERE nurse_user = ?',
            ['nurse1']
        ));
    }

    public function testRejectsDuplicateCredentialsAndRollsBackBatch(): void
    {
        $data = $this->validData();
        $data['nurses'][0]['credentials'][] = $data['nurses'][0]['credentials'][0];
        file_put_contents($this->file, json_encode($data, JSON_THROW_ON_ERROR));

        try {
            (new NurseCredentialImporter($this->connection))->importFile($this->file);
            self::fail('Expected duplicate credential validation to fail.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('Duplicate credential', $exception->getMessage());
        }

        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM nurse_credentials'));
    }

    public function testRollsBackNewRecordsWhenCredentialAlreadyExistsInDatabase(): void
    {
        $existing = $this->validData()['nurses'][0]['credentials'][0];
        $this->connection->insert('nurse_credentials', [
            'nurse_user' => 'nurse1',
            'license_number' => $existing['licenseNumber'],
            'certification' => $existing['certification'],
            'issuing_body' => $existing['issuingBody'],
            'issue_date' => $existing['issueDate'],
            'expiration_date' => $existing['expirationDate'],
        ]);

        $newCredential = $existing;
        $newCredential['licenseNumber'] = 'RN-99999';
        $data = $this->validData();
        $data['nurses'][0]['credentials'] = [$newCredential, $existing];
        file_put_contents($this->file, json_encode($data, JSON_THROW_ON_ERROR));

        try {
            (new NurseCredentialImporter($this->connection))->importFile($this->file);
            self::fail('Expected the existing database credential to fail the import.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('Credential already exists', $exception->getMessage());
        }

        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM nurse_credentials'));
    }

    public function testRejectsInvalidLicenseNumber(): void
    {
        $data = $this->validData();
        $data['nurses'][0]['credentials'][0]['licenseNumber'] = 'bad!';
        file_put_contents($this->file, json_encode($data, JSON_THROW_ON_ERROR));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid license number');
        (new NurseCredentialImporter($this->connection))->importFile($this->file);
    }

    public function testRejectsMissingExpirationDate(): void
    {
        $data = $this->validData();
        unset($data['nurses'][0]['credentials'][0]['expirationDate']);
        file_put_contents($this->file, json_encode($data, JSON_THROW_ON_ERROR));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing expirationDate');
        (new NurseCredentialImporter($this->connection))->importFile($this->file);
    }

    public function testRejectsIncompleteCertificationInformation(): void
    {
        $data = $this->validData();
        $data['nurses'][0]['credentials'][0]['certification'] = ' ';
        file_put_contents($this->file, json_encode($data, JSON_THROW_ON_ERROR));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing or invalid certification');
        (new NurseCredentialImporter($this->connection))->importFile($this->file);
    }

    public function testRejectsInvalidIssuingBody(): void
    {
        $data = $this->validData();
        $data['nurses'][0]['credentials'][0]['issuingBody'] = '??';
        file_put_contents($this->file, json_encode($data, JSON_THROW_ON_ERROR));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid issuing body');
        (new NurseCredentialImporter($this->connection))->importFile($this->file);
    }

    public function testRejectsIncorrectlyFormattedExpirationDate(): void
    {
        $data = $this->validData();
        $data['nurses'][0]['credentials'][0]['expirationDate'] = '03/15/2027';
        file_put_contents($this->file, json_encode($data, JSON_THROW_ON_ERROR));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid expirationDate');
        (new NurseCredentialImporter($this->connection))->importFile($this->file);
    }

    private function validData(): array
    {
        return [
            'schemaVersion' => 1,
            'nurses' => [
                [
                    'user' => 'nurse1',
                    'password' => 'n1pass',
                    'credentials' => [
                        [
                            'licenseNumber' => 'RN-12345',
                            'certification' => 'Basic Life Support',
                            'issuingBody' => 'American Red Cross',
                            'issueDate' => '2025-01-01',
                            'expirationDate' => (new \DateTimeImmutable('+1 year'))->format('Y-m-d'),
                        ],
                    ],
                ],
            ],
        ];
    }
}
