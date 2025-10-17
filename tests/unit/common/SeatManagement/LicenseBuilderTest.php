<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use DateTimeImmutable;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Ramsey\Uuid\Uuid;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Option\Option;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\SeatManagement\CheckLicenseContentStub;
use Tuleap\Test\Stubs\SeatManagement\CheckLicenseSignatureStub;
use Tuleap\Test\Stubs\SeatManagement\CheckPublicKeyPresenceStub;
use Tuleap\Test\Stubs\SeatManagement\RetrieveLicenseContentStub;
use function Psl\Filesystem\create_directory;
use function Psl\Filesystem\create_file;
use function Psl\Filesystem\delete_file;

#[DisableReturnValueGenerationForTestDoubles]
final class LicenseBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    /** @var non-empty-string */
    private string $license_file_path;

    #[\Override]
    protected function setUp(): void
    {
        $temp_dir                = vfsStream::setup()->url();
        $conf_directory          = $temp_dir . '/conf';
        $this->license_file_path = "$conf_directory/license.key";
        \ForgeConfig::set('sys_custom_dir', $temp_dir);

        create_directory($conf_directory);
        create_file($this->license_file_path);
    }

    public function testItShouldReturnTCEWhenThereIsNoKey(): void
    {
        $builder = new LicenseBuilder(
            CheckPublicKeyPresenceStub::buildWithNoKey(),
            CheckLicenseSignatureStub::buildWithValidSignature(),
            RetrieveLicenseContentStub::buildWithoutLicenseContent(),
            CheckLicenseContentStub::buildWithInvalid(),
        );

        $license = $builder->build();
        self::assertSame(false, $license->is_enterprise_edition);
        self::assertEquals(Option::nothing(DateTimeImmutable::class), $license->expiration_date);
        self::assertSame([], $license->restrictions);
        self::assertSame(true, $license->has_valid_signature);
    }

    public function testItShouldReturnTEEWithAMigrationDateWhenThereIsNoLicenseFile(): void
    {
        $builder = new LicenseBuilder(
            CheckPublicKeyPresenceStub::buildWithKey(),
            CheckLicenseSignatureStub::buildWithValidSignature(),
            RetrieveLicenseContentStub::buildWithoutLicenseContent(),
            CheckLicenseContentStub::buildWithInvalid(),
        );
        delete_file($this->license_file_path);

        $license = $builder->build();
        self::assertSame(true, $license->is_enterprise_edition);
        self::assertEquals(Option::nothing(DateTimeImmutable::class), $license->expiration_date);
        self::assertSame([], $license->restrictions);
        self::assertSame(true, $license->has_valid_signature);
    }

    public function testItShouldReturnExpiredTEEIfLicenseSignatureIsInvalid(): void
    {
        $builder = new LicenseBuilder(
            CheckPublicKeyPresenceStub::buildWithKey(),
            CheckLicenseSignatureStub::buildWithInvalidSignature(),
            RetrieveLicenseContentStub::buildWithoutLicenseContent(),
            CheckLicenseContentStub::buildWithInvalid(),
        );

        $license = $builder->build();
        self::assertSame(true, $license->is_enterprise_edition);
        self::assertEquals(Option::nothing(DateTimeImmutable::class), $license->expiration_date);
        self::assertSame([], $license->restrictions);
        self::assertSame(false, $license->has_valid_signature);
    }

    public function testItShouldReturnExpiredTEEIfLicenseContentIsInvalid(): void
    {
        $builder = new LicenseBuilder(
            CheckPublicKeyPresenceStub::buildWithKey(),
            CheckLicenseSignatureStub::buildWithValidSignature(),
            RetrieveLicenseContentStub::buildWithoutLicenseContent(),
            CheckLicenseContentStub::buildWithInvalid(),
        );

        $license = $builder->build();
        self::assertSame(true, $license->is_enterprise_edition);
        self::assertEquals(Option::nothing(DateTimeImmutable::class), $license->expiration_date);
        self::assertSame([], $license->restrictions);
        self::assertSame(false, $license->has_valid_signature);
    }

    public function testItShouldReturnInvalidTEEWhenLicenseClaimsAreInvalid(): void
    {
        $builder = new LicenseBuilder(
            CheckPublicKeyPresenceStub::buildWithKey(),
            CheckLicenseSignatureStub::buildWithValidSignature(),
            RetrieveLicenseContentStub::buildWithLicenseContent(new LicenseContent(
                'enalean-tuleap-enterprise',
                ['toto'],
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                Uuid::uuid4(),
                [],
                null,
                null,
            )),
            CheckLicenseContentStub::buildWithInvalid(),
        );

        $license = $builder->build();
        self::assertSame(true, $license->is_enterprise_edition);
        self::assertEquals(Option::nothing(DateTimeImmutable::class), $license->expiration_date);
        self::assertSame([], $license->restrictions);
        self::assertSame(false, $license->has_valid_signature);
    }

    public function testItShouldReturnValidTEE(): void
    {
        $date            = new DateTimeImmutable()->modify('+1 year');
        $license_content = new LicenseContent(
            'enalean-tuleap-enterprise',
            ['toto'],
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            Uuid::uuid7($date),
            [],
            $date,
            null,
        );
        $builder         = new LicenseBuilder(
            CheckPublicKeyPresenceStub::buildWithKey(),
            CheckLicenseSignatureStub::buildWithValidSignature(),
            RetrieveLicenseContentStub::buildWithLicenseContent($license_content),
            CheckLicenseContentStub::buildWithValid(),
        );

        self::assertEquals(License::buildEnterpriseEdition($license_content), $builder->build());
    }
}
