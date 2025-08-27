<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\REST;

trait ForgeAccessSandbox
{
    private string $site_access;

    #[\PHPUnit\Framework\Attributes\Before]
    public function backupSiteAccess(): void
    {
        $this->site_access = TuleapConfig::instance()->getAccess();
    }

    #[\PHPUnit\Framework\Attributes\After]
    public function restoreSiteAccess(): void
    {
        match ($this->site_access) {
            TuleapConfig::ANONYMOUS => TuleapConfig::instance()->setForgeToAnonymous(),
            TuleapConfig::REGULAR => TuleapConfig::instance()->setForgeToRegular(),
            TuleapConfig::RESTRICTED => TuleapConfig::instance()->setForgeToRestricted(),
        };
    }

    public function setForgeToAnonymous(): void
    {
        TuleapConfig::instance()->setForgeToAnonymous();
    }

    public function setForgeToRestricted(): void
    {
        TuleapConfig::instance()->setForgeToRestricted();
    }

    public function setForgeToRegular(): void
    {
        TuleapConfig::instance()->setForgeToRegular();
    }
}
