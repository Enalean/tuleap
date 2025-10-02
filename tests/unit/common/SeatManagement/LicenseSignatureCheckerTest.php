<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\SeatManagement;

use ColinODell\PsrTestLogger\TestLogger;
use CuyZ\Valinor\MapperBuilder;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Validator;
use org\bovigo\vfs\vfsStream;
use Psl\File\WriteMode;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Tuleap\Test\Builders\SeatManagement\PublicKeyTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use function Psl\File\write;
use function Psl\Filesystem\create_directory;
use function Psl\Filesystem\create_file;
use function Psl\Filesystem\delete_directory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LicenseSignatureCheckerTest extends TestCase
{
    /** @var non-empty-string */
    private string $license_file_path;
    /** @var non-empty-string */
    private string $keys_directory;

    #[\Override]
    protected function setUp(): void
    {
        $temp_dir                = vfsStream::setup()->url();
        $this->license_file_path = $temp_dir . '/license.key';
        $this->keys_directory    = $temp_dir . '/keys';

        create_directory($this->keys_directory);
        create_file($this->license_file_path);
    }

    public function testItReturnsFalseWhenLicenseFileIsEmpty(): void
    {
        new PublicKeyTestBuilder($this->license_file_path, $this->keys_directory)->build();
        write($this->license_file_path, '', WriteMode::Truncate);

        $logger          = new TestLogger();
        $license_checker = new LicenseSignatureChecker(
            $logger,
            new Parser(new JoseEncoder()),
            new Validator(),
            new MapperBuilder()->registerConstructor(Uuid::fromString(...))->mapper(),
        );

        self::assertFalse($license_checker->checkLicenseSignature($this->license_file_path, $this->keys_directory));
        self::assertTrue($logger->hasError('License file is empty'));
    }

    public function testItReturnsFalseWhenLicenseFileContentDoesNotHave3Parts(): void
    {
        new PublicKeyTestBuilder($this->license_file_path, $this->keys_directory)->build();
        write($this->license_file_path, 'Some text', WriteMode::Truncate);

        $logger          = new TestLogger();
        $license_checker = new LicenseSignatureChecker(
            $logger,
            new Parser(new JoseEncoder()),
            new Validator(),
            new MapperBuilder()->registerConstructor(Uuid::fromString(...))->mapper(),
        );

        self::assertFalse($license_checker->checkLicenseSignature($this->license_file_path, $this->keys_directory));
        self::assertTrue($logger->hasError('Failed parsing license: The JWT string must have two dots'));
    }

    public function testItReturnsFalseWhenLicenseHeaderContentIsInvalid(): void
    {
        new PublicKeyTestBuilder($this->license_file_path, $this->keys_directory)->withoutKidHeader()->build();

        $logger          = new TestLogger();
        $license_checker = new LicenseSignatureChecker(
            $logger,
            new Parser(new JoseEncoder()),
            new Validator(),
            new MapperBuilder()->registerConstructor(Uuid::fromString(...))->mapper(),
        );

        self::assertFalse($license_checker->checkLicenseSignature($this->license_file_path, $this->keys_directory));
        self::assertTrue($logger->hasErrorThatContains('Failed parsing license headers: Could not map type `Tuleap\SeatManagement\LicenseHeaders`.'));
    }

    public function testItReturnsFalseWhenLicenseCorrespondingPublicKeyDoesNotExist(): void
    {
        new PublicKeyTestBuilder($this->license_file_path, $this->keys_directory)->build();
        delete_directory($this->keys_directory, true);

        $logger          = new TestLogger();
        $license_checker = new LicenseSignatureChecker(
            $logger,
            new Parser(new JoseEncoder()),
            new Validator(),
            new MapperBuilder()->registerConstructor(Uuid::fromString(...))->mapper(),
        );

        self::assertFalse($license_checker->checkLicenseSignature($this->license_file_path, $this->keys_directory));
        self::assertTrue($logger->hasError('License uses non-existent public key.'));
    }

    public function testItReturnsFalseWhenPublicKeyFormatIsInvalid(): void
    {
        $key_file = new PublicKeyTestBuilder($this->license_file_path, $this->keys_directory)->build();
        write($key_file, 'Some text', WriteMode::Truncate);

        $logger          = new TestLogger();
        $license_checker = new LicenseSignatureChecker(
            $logger,
            new Parser(new JoseEncoder()),
            new Validator(),
            new MapperBuilder()->registerConstructor(Uuid::fromString(...))->mapper(),
        );

        self::assertFalse($license_checker->checkLicenseSignature($this->license_file_path, $this->keys_directory));
        self::assertTrue($logger->hasError('Failed parsing public key: Invalid JSON source.'));
    }

    public function testItReturnsFalseWhenLicenseSignatureIsIncorrect(): void
    {
        new PublicKeyTestBuilder($this->license_file_path, $this->keys_directory)->withInvalidSignature()->build();

        $logger          = new TestLogger();
        $license_checker = new LicenseSignatureChecker(
            $logger,
            new Parser(new JoseEncoder()),
            new Validator(),
            new MapperBuilder()->registerConstructor(Uuid::fromString(...))->mapper(),
        );

        self::assertFalse($license_checker->checkLicenseSignature($this->license_file_path, $this->keys_directory));
        self::assertFalse($logger->hasErrorRecords());
    }

    public function testItReturnsTrueWhenLicenseSignatureIsCorrect(): void
    {
        new PublicKeyTestBuilder($this->license_file_path, $this->keys_directory)->build();

        $license_checker = new LicenseSignatureChecker(
            new NullLogger(),
            new Parser(new JoseEncoder()),
            new Validator(),
            new MapperBuilder()->registerConstructor(Uuid::fromString(...))->mapper(),
        );

        self::assertTrue($license_checker->checkLicenseSignature($this->license_file_path, $this->keys_directory));
    }

    public function testItReturnsTrueForTheDevLicense(): void
    {
        $license_checker = new LicenseSignatureChecker(
            new NullLogger(),
            new Parser(new JoseEncoder()),
            new Validator(),
            new MapperBuilder()->registerConstructor(Uuid::fromString(...))->mapper(),
        );

        self::assertTrue($license_checker->checkLicenseSignature(
            __DIR__ . '/../../../../tools/docker/tuleap-aio-dev/license.key',
            __DIR__ . '/../../../../src/common/SeatManagement/keys',
        ));
    }
}
