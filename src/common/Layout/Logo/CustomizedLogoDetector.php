<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Layout\Logo;

use ForgeConfig;
use LogoRetriever;

readonly class CustomizedLogoDetector implements IDetectIfLogoIsCustomized
{
    private const array ORGANIZATION_LOGO_SHA256_CONTENT_HASHES = [
        self::BURNING_PARROT_DEFAULT_ORGANIZATION_LOGO_SHA256_HASH,
        self::COMMON_THEME_DEFAULT_ORGANIZATION_LOGO_SHA256_HASH,
        self::N_MINUS_ONE_CRUNCH_LOGO_SHA256_HASH,
        self::N_MINUS_ONE_NON_OPTIMIZED_LOGO_SHA256_HASH,
    ];

    private const string BURNING_PARROT_DEFAULT_ORGANIZATION_LOGO_SHA256_HASH = '5620ae654644bb1d7a71ce659be0b2445e6899d49a003a84ebc6d0fe05270b5c';
    private const string COMMON_THEME_DEFAULT_ORGANIZATION_LOGO_SHA256_HASH   = 'b61210cf44a87ced4209119267751439343f25da8f872feedebae1dea3b04277';
    private const string N_MINUS_ONE_CRUNCH_LOGO_SHA256_HASH                  = '1eabd948d8d077314370f9ee3b76c5e8bbf70ab993f1e3eea49c32074333cbdf';
    private const string N_MINUS_ONE_NON_OPTIMIZED_LOGO_SHA256_HASH           = 'f6aeea978b22cd40c9804fd1e897ad394643a3715fa8e6ab449dd18397dce1c0';

    public function __construct(
        private LogoRetriever $logo_retriever,
        private FileContentComparator $comparator,
    ) {
    }

    #[\Override]
    public function isLegacyOrganizationLogoCustomized(): bool
    {
        if (! $this->isLegacyOrganizationLogoDeployed()) {
            return false;
        }

        return $this->isLegacyOrganizationLogoDifferentThanOurs();
    }

    #[\Override]
    public function isSvgOrganizationLogoCustomized(): bool
    {
        return $this->logo_retriever->getSvgPath() !== null
            && $this->logo_retriever->getSmallSvgPath() !== null;
    }

    private function isLegacyOrganizationLogoDeployed(): bool
    {
        return $this->logo_retriever->getLegacyPath() !== null;
    }

    private function isLegacyOrganizationLogoDifferentThanOurs(): bool
    {
        return ! $this->comparator->doesFilesHaveTheSameContent(
            self::ORGANIZATION_LOGO_SHA256_CONTENT_HASHES,
            ForgeConfig::get('sys_data_dir') . '/images/organization_logo.png',
        );
    }
}
