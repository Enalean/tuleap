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

use _PHPStan_b0aa47e74\Symfony\Component\Console\Exception\LogicException;
use DateTimeImmutable;
use ForgeConfig;
use Override;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\SeatManagement\Fault\InvalidLicenseContentFault;
use Tuleap\ServerHostname;
use function Psl\Iter\contains;
use function Psl\Regex\matches;

final readonly class LicenseContentChecker implements CheckLicenseContent
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    #[Override]
    public function checkLicenseContent(LicenseContent $license_content): Ok|Err
    {
        return $this->checkAUD($license_content)
            ->andThen($this->checkIATAndNBF(...))
            ->andThen($this->checkJTI(...));
    }

    /**
     * @return Ok<LicenseContent>|Err<Fault>
     */
    private function checkAUD(LicenseContent $license_content): Ok|Err
    {
        $hostname = ForgeConfig::get(ServerHostname::DEFAULT_DOMAIN);
        if (! matches($hostname, '/:\d+$/')) {
            $hostname = $hostname . ':443';
        }
        assert($hostname !== ''); // At this point we are sure it is not empty
        $hostname = hash('sha256', (string) idn_to_ascii($hostname, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46));

        if (! contains($license_content->aud, $hostname)) {
            $this->logger->info('Invalid license aud: sys_default_domain not in it');
            return Result::err(InvalidLicenseContentFault::build());
        }

        return Result::ok($license_content);
    }

    /**
     * @return Ok<LicenseContent>|Err<Fault>
     */
    private function checkIATAndNBF(LicenseContent $license_content): Ok|Err
    {
        if ($license_content->iat > new DateTimeImmutable('now')) {
            $this->logger->info('Invalid license iat: it cannot be in future');
            return Result::err(InvalidLicenseContentFault::build());
        }

        if ($license_content->nbf < $license_content->iat) {
            $this->logger->info('Invalid license nbf: it cannot be before iat');
            return Result::err(InvalidLicenseContentFault::build());
        }

        return Result::ok($license_content);
    }

    private function checkJTI(LicenseContent $license_content): Ok|Err
    {
        $has_expiration_date = $license_content->exp !== null;
        $uuid_fields         = $license_content->jti->getFields();
        if (! ($uuid_fields instanceof FieldsInterface)) {
            throw new LogicException('UUID fields are not of type ' . FieldsInterface::class);
        }

        if ($has_expiration_date && $uuid_fields->getVersion() !== 7) {
            $this->logger->info('Invalid license jti: expected a UUIDv7');
            return Result::err(InvalidLicenseContentFault::build());
        }

        if (! $has_expiration_date && $uuid_fields->getVersion() !== 4) {
            $this->logger->info('Invalid license jti: expected a UUIDv4');
            return Result::err(InvalidLicenseContentFault::build());
        }

        return Result::ok($license_content);
    }
}
