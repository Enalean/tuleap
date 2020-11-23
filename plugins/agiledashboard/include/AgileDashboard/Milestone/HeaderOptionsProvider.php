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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_PaneInfoIdentifier;
use Layout;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;

class HeaderOptionsProvider
{
    /**
     * @var AgileDashboard_PaneInfoIdentifier
     */
    private $pane_info_identifier;
    /**
     * @var HeaderOptionsForPlanningProvider
     */
    private $header_options_for_planning_provider;

    public function __construct(
        AgileDashboard_PaneInfoIdentifier $pane_info_identifier,
        HeaderOptionsForPlanningProvider $header_options_for_planning_provider
    ) {
        $this->pane_info_identifier                 = $pane_info_identifier;
        $this->header_options_for_planning_provider = $header_options_for_planning_provider;
    }

    public function getHeaderOptions(\PFUser $user, \Planning_Milestone $milestone, string $identifier): array
    {
        $is_pane_a_planning_v2 = $this->pane_info_identifier->isPaneAPlanningV2($identifier);

        $header_options = [
            Layout::INCLUDE_FAT_COMBINED => ! $is_pane_a_planning_v2,
            'body_class'                 => ['agiledashboard-body']
        ];

        if ($is_pane_a_planning_v2) {
            $this->header_options_for_planning_provider->addPlanningOptions($user, $milestone, $header_options);
        }

        return $header_options;
    }
}
