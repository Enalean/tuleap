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

use LogicException;
use Override;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\SeatManagement\Fault\InvalidLicenseContentFault;
use Tuleap\SeatManagement\Fault\InvalidLicenseSignatureFault;
use Tuleap\SeatManagement\Fault\LicenseClaimsParsingFault;
use Tuleap\SeatManagement\Fault\MissingLicenseFileFault;
use Tuleap\SeatManagement\Fault\NoPublicKeyFault;
use function Psl\Filesystem\is_file;

final readonly class LicenseBuilder implements BuildLicense
{
    private const string PUBLIC_KEY_DIRECTORY = __DIR__ . '/keys';

    public function __construct(
        private CheckPublicKeyPresence $public_key_presence_checker,
        private CheckLicenseSignature $license_signature_checker,
        private RetrieveLicenseContent $license_content_retriever,
        private CheckLicenseContent $license_content_checker,
    ) {
    }

    #[Override]
    public function build(): License
    {
        return $this->public_key_presence_checker->checkPresence(self::PUBLIC_KEY_DIRECTORY)
            ->andThen($this->checkLicenseFilePresence(...))
            ->andThen(
            /**
             * @param non-empty-string $license_file_path
             */
                function (string $license_file_path) {
                    return $this->license_signature_checker->checkLicenseSignature($license_file_path, self::PUBLIC_KEY_DIRECTORY);
                }
            )
            ->andThen($this->license_content_retriever->retrieveLicenseContent(...))
            ->andThen($this->license_content_checker->checkLicenseContent(...))
            ->match(
                static fn(LicenseContent $license_content) => License::buildEnterpriseEdition($license_content),
                static fn(Fault $fault) => match ($fault::class) {
                    NoPublicKeyFault::class           => License::buildCommunityEdition(),
                    MissingLicenseFileFault::class    => License::buildInfiniteEnterpriseEdition(),
                    LicenseClaimsParsingFault::class,
                    InvalidLicenseSignatureFault::class,
                    InvalidLicenseContentFault::class => License::buildInvalidEnterpriseEdition(),
                    default                           => throw new LogicException('Was not expected: ' . $fault::class),
                },
            );
    }

    /**
     * @return Ok<non-empty-string>|Err<Fault>
     */
    private function checkLicenseFilePresence(): Ok|Err
    {
        /** @var non-empty-string $license_file_path */
        $license_file_path = ((string) \ForgeConfig::get('sys_custom_dir')) . '/conf/license.key';
        if (! is_file($license_file_path)) {
            return Result::err(MissingLicenseFileFault::build());
        }
        return Result::ok($license_file_path);
    }
}
