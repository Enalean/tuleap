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

use Override;
use function Psl\Filesystem\is_file;

final readonly class LicenseBuilder implements BuildLicense
{
    private const string PUBLIC_KEY_DIRECTORY = __DIR__ . '/keys';

    public function __construct(
        private CheckPublicKeyPresence $public_key_presence_checker,
        private CheckLicenseSignature $license_signature_checker,
        private RetrieveLicenseContent $license_content_retriever,
    ) {
    }

    #[Override]
    public function build(): License
    {
        $is_public_key_present = $this->public_key_presence_checker->checkPresence(self::PUBLIC_KEY_DIRECTORY);

        if (! $is_public_key_present) {
            return License::buildCommunityEdition();
        }

        $license_file_path = ((string) \ForgeConfig::get('sys_custom_dir')) . '/conf/license.key';
        if (! is_file($license_file_path)) {
            return License::buildEnterpriseEdition(null);
        }

        if (! $this->license_signature_checker->checkLicenseSignature($license_file_path, self::PUBLIC_KEY_DIRECTORY)) {
            return License::buildInvalidEnterpriseEdition();
        }

        return $this->license_content_retriever->retrieveLicenseContent($license_file_path)
            ->match(
                static fn(LicenseContent $license_content) => License::buildEnterpriseEdition($license_content->exp),
                static fn() => License::buildInvalidEnterpriseEdition(),
            );
    }
}
