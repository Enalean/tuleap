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

use ColinODell\PsrTestLogger\TestLogger;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Ramsey\Uuid\Uuid;
use Tuleap\NeverThrow\Result;
use Tuleap\SeatManagement\Fault\LicenseClaimsParsingFault;
use Tuleap\Test\Builders\SeatManagement\PublicKeyTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use function Psl\File\read;
use function Psl\Filesystem\create_directory;
use function Psl\Filesystem\create_file;

#[DisableReturnValueGenerationForTestDoubles]
final class LicenseContentRetrieverTest extends TestCase
{
    /** @var non-empty-string */
    private string $license_file_path;
    /** @var non-empty-string */
    private string $keys_directory;
    private TreeMapper $mapper;

    #[\Override]
    protected function setUp(): void
    {
        $temp_dir                = vfsStream::setup()->url();
        $this->license_file_path = $temp_dir . '/license.key';
        $this->keys_directory    = $temp_dir . '/keys';

        create_directory($this->keys_directory);
        create_file($this->license_file_path);

        $this->mapper = new MapperBuilder()->registerConstructor(Uuid::fromString(...))->allowSuperfluousKeys()->allowPermissiveTypes()->allowUndefinedValues()->mapper();
    }

    public function testItShouldReturnNothingWhenLicenseClaimsAreInvalid(): void
    {
        $builder = new PublicKeyTestBuilder($this->license_file_path, $this->keys_directory)->withoutAudClaim();
        $builder->build();

        $logger    = new TestLogger();
        $retriever = new LicenseContentRetriever(
            $logger,
            $this->mapper,
        );
        $result    = $retriever->retrieveLicenseContent($builder->getToken());

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(LicenseClaimsParsingFault::class, $result->error);
        self::assertTrue($logger->hasInfoThatContains('Failed parsing license claims: Could not map type `Tuleap\SeatManagement\LicenseContent`'));
    }

    public function testItReturnsALicenseContent(): void
    {
        $builder = new PublicKeyTestBuilder($this->license_file_path, $this->keys_directory);
        $builder->build();

        $retriever = new LicenseContentRetriever(
            new NullLogger(),
            $this->mapper,
        );

        self::assertTrue(Result::isOk($retriever->retrieveLicenseContent($builder->getToken())));
    }

    public function testItReturnsLicenseContentForTheDevLicense(): void
    {
        $retriever = new LicenseContentRetriever(
            new NullLogger(),
            $this->mapper,
        );

        $jwt = trim(read(__DIR__ . '/../../../../tools/docker/tuleap-aio-dev/license.key'));
        self::assertTrue($jwt !== '');
        $token = new Parser(new JoseEncoder())->parse($jwt);
        self::assertInstanceOf(UnencryptedToken::class, $token);

        $result = $retriever->retrieveLicenseContent($token);
        self::assertTrue(Result::isOk($result));
        $license = $result->value;
        self::assertInstanceOf(LicenseContent::class, $license);
        self::assertSame([
            'dc41ac2eb04e9fd5804e02dfe34706450b40f060b278551671d6f49f55d8131a',
            '6c22a451519aeefead4e126a00d7f313e28555026c5c686c151bbf16f25441be',
            '3b18c52f134a940ddd919252acdd4fb0a34442487da4f40b4ea875c9b085184d',
        ], $license->aud);
        self::assertSame('enalean-tuleap-enterprise', $license->iss);
        $uuid_fields = $license->jti->getFields();
        self::assertInstanceOf(FieldsInterface::class, $uuid_fields);
        self::assertSame(4, $uuid_fields->getVersion());
        self::assertEmpty($license->restrictions);
    }
}
