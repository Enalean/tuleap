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

class CustomizedLogoDetector implements IDetectIfLogoIsCustomized
{
    /**
     * @var LogoRetriever
     */
    private $logo_retriever;
    /**
     * @var FileContentComparator
     */
    private $comparator;

    public function __construct(LogoRetriever $logo_retriever, FileContentComparator $comparator)
    {
        $this->logo_retriever = $logo_retriever;
        $this->comparator     = $comparator;
    }

    public function isLegacyOrganizationLogoCustomized(): bool
    {
        if (! $this->isLegacyOrganizationLogoDeployed()) {
            return false;
        }

        return $this->isLegacyOrganizationLogoDifferentThanOurs();
    }

    private function isLegacyOrganizationLogoDeployed(): bool
    {
        return $this->logo_retriever->getPath() !== null;
    }

    private function isLegacyOrganizationLogoDifferentThanOurs(): bool
    {
        return ! $this->comparator->doesFilesHaveTheSameContent(
            __DIR__ . '/../../../www/themes/BurningParrot/images/organization_logo.png',
            ForgeConfig::get('sys_data_dir') . '/images/organization_logo.png',
        );
    }
}
