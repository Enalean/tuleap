<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\AgileDashboard\Milestone\Sidebar;

use Tuleap\AgileDashboard\Milestone\Sidebar\UpdateMilestonesInSidebarConfig;

final class MilestonesInSidebarXmlImport
{
    public function __construct(private readonly UpdateMilestonesInSidebarConfig $milestones_in_sidebar_config)
    {
    }

    public function import(\SimpleXMLElement $xml, \Project $project): void
    {
        if ("0" === (string) $xml['should_sidebar_display_last_milestones']) {
            $this->milestones_in_sidebar_config->deactivateMilestonesInSidebar((int) $project->getID());
        }
    }
}
