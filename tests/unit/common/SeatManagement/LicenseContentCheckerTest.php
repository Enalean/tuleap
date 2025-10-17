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
use CuyZ\Valinor\MapperBuilder;
use DateTimeImmutable;
use ForgeConfig;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Tuleap\ForgeConfigSandbox;
use Tuleap\NeverThrow\Result;
use Tuleap\SeatManagement\Fault\InvalidLicenseContentFault;
use Tuleap\ServerHostname;
use Tuleap\Test\PHPUnit\TestCase;
use function Psl\File\read;

#[DisableReturnValueGenerationForTestDoubles]
final class LicenseContentCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var non-empty-string
     */
    private string $domain_hash;

    #[Override]
    protected function setUp(): void
    {
        ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, 'example.com');
        $this->domain_hash = hash('sha256', (string) idn_to_ascii('example.com:443', IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46));
    }

    public function testItShouldReturnsFaultWhenWrongAUD(): void
    {
        $logger  = new TestLogger();
        $checker = new LicenseContentChecker($logger);

        $result = $checker->checkLicenseContent(new LicenseContent(
            'enalean-tuleap-enterprise',
            ['abc'],
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            Uuid::uuid4(),
            [],
            null,
            null,
        ));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidLicenseContentFault::class, $result->error);
        self::assertTrue($logger->hasInfo('Invalid license aud: sys_default_domain not in it'));
    }

    public function testItShouldReturnsFaultWhenIATIsInFuture(): void
    {
        $logger  = new TestLogger();
        $checker = new LicenseContentChecker($logger);

        $result = $checker->checkLicenseContent(new LicenseContent(
            'enalean-tuleap-enterprise',
            [$this->domain_hash],
            new DateTimeImmutable('now')->modify('+1 year'),
            new DateTimeImmutable(),
            Uuid::uuid4(),
            [],
            null,
            null,
        ));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidLicenseContentFault::class, $result->error);
        self::assertTrue($logger->hasInfo('Invalid license iat: it cannot be in future'));
    }

    public function testItShouldReturnsFaultWhenNBFIsBeforeIAT(): void
    {
        $logger  = new TestLogger();
        $checker = new LicenseContentChecker($logger);

        $result = $checker->checkLicenseContent(new LicenseContent(
            'enalean-tuleap-enterprise',
            [$this->domain_hash],
            new DateTimeImmutable('now')->modify('-1 day'),
            new DateTimeImmutable('now')->modify('-1 week'),
            Uuid::uuid4(),
            [],
            null,
            null,
        ));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidLicenseContentFault::class, $result->error);
        self::assertTrue($logger->hasInfo('Invalid license nbf: it cannot be before iat'));
    }

    public function testItShouldReturnsFaultWhenHasExpirationButJTIIsUUIDv4(): void
    {
        $logger  = new TestLogger();
        $checker = new LicenseContentChecker($logger);

        $result = $checker->checkLicenseContent(new LicenseContent(
            'enalean-tuleap-enterprise',
            [$this->domain_hash],
            new DateTimeImmutable('now'),
            new DateTimeImmutable('now'),
            Uuid::uuid4(),
            [],
            new DateTimeImmutable(),
            null,
        ));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidLicenseContentFault::class, $result->error);
        self::assertTrue($logger->hasInfo('Invalid license jti: expected a UUIDv7'));
    }

    public function testItShouldReturnsFaultWhenNoExpirationButJTIIsUUIDv7(): void
    {
        $logger  = new TestLogger();
        $checker = new LicenseContentChecker($logger);

        $result = $checker->checkLicenseContent(new LicenseContent(
            'enalean-tuleap-enterprise',
            [$this->domain_hash],
            new DateTimeImmutable('now'),
            new DateTimeImmutable('now'),
            Uuid::uuid7(),
            [],
            null,
            null,
        ));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidLicenseContentFault::class, $result->error);
        self::assertTrue($logger->hasInfo('Invalid license jti: expected a UUIDv4'));
    }

    public function testItShouldReturnsLicenseContent(): void
    {
        $checker = new LicenseContentChecker(new NullLogger());

        $result = $checker->checkLicenseContent(new LicenseContent(
            'enalean-tuleap-enterprise',
            [$this->domain_hash],
            new DateTimeImmutable('now'),
            new DateTimeImmutable('now'),
            Uuid::uuid4(),
            [],
            null,
            null,
        ));

        self::assertTrue(Result::isOk($result));
    }

    public function testItShouldReturnsLicenseContentForDevLicense(): void
    {
        ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, 'tuleap-web.tuleap-aio-dev.docker');
        $retriever = new LicenseContentRetriever(
            new NullLogger(),
            new MapperBuilder()->registerConstructor(Uuid::fromString(...))->allowSuperfluousKeys()->allowPermissiveTypes()->allowUndefinedValues()->mapper(),
        );

        $jwt = trim(read(__DIR__ . '/../../../../tools/docker/tuleap-aio-dev/license.key'));
        self::assertTrue($jwt !== '');
        $token = new Parser(new JoseEncoder())->parse($jwt);
        self::assertInstanceOf(UnencryptedToken::class, $token);

        $license_content = $retriever->retrieveLicenseContent($token)->unwrapOr(null);
        self::assertNotNull($license_content);
        $checker = new LicenseContentChecker(new NullLogger());

        $result = $checker->checkLicenseContent($license_content);
        self::assertTrue(Result::isOk($result));
    }
}
