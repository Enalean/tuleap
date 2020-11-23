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

class HeaderOptionsProvider
{
    /**
     * @var AgileDashboard_PaneInfoIdentifier
     */
    private $pane_info_identifier;

    public function __construct(AgileDashboard_PaneInfoIdentifier $pane_info_identifier)
    {
        $this->pane_info_identifier = $pane_info_identifier;
    }

    public function getHeaderOptions(string $identifier): array
    {
        $is_pane_a_planning_v2 = $this->pane_info_identifier->isPaneAPlanningV2($identifier);

        $header_options = [
            Layout::INCLUDE_FAT_COMBINED => ! $is_pane_a_planning_v2,
            'body_class'                 => ['agiledashboard-body']
        ];

        if ($is_pane_a_planning_v2) {
            $header_options['body_class'][] = 'has-sidebar-with-pinned-header';
        }

        return $header_options;
    }
}
