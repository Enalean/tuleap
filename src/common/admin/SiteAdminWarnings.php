<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Admin;

use Event;
use EventManager;
use ForgeUpgradeConfig;
use Tuleap\Admin\Homepage\NbUsersByStatus;

final class SiteAdminWarnings
{
    private EventManager $event_manager;
    private ForgeUpgradeConfig $forge_upgrade_config;

    public function __construct(EventManager $event_manager, ForgeUpgradeConfig $forge_upgrade_config)
    {
        $this->event_manager        = $event_manager;
        $this->forge_upgrade_config = $forge_upgrade_config;
    }

    public function getAdminHomeWarningsWithUsersByStatus(NbUsersByStatus $nb_users_by_status): string
    {
        $warnings = [];
        $this->event_manager->processEvent(
            Event::GET_SITEADMIN_WARNINGS,
            [
                'nb_users_by_status' => $nb_users_by_status,
                'warnings'           => &$warnings,
            ]
        );

        if (! $this->forge_upgrade_config->isSystemUpToDate()) {
            $warnings[] = '<div class="tlp-alert-warning alert alert-warning alert-block">' . _('<h4>ForgeUpgrade was not run!</h4><p>It seems that someone upgraded Tuleap RPMs without running forgeupgrade command. Please check <a href="/doc/en/installation-guide/update.html">upgrade documentation</a>.</p>') . '</div>';
        }

        return implode('', $warnings);
    }
}
