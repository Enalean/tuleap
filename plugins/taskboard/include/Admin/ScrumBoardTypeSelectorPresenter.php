<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Admin;

use Tuleap\Taskboard\AgileDashboard\TaskboardUsage;

class ScrumBoardTypeSelectorPresenter
{
    /**
     * @var array
     */
    public $board_types;

    public function __construct(string $current_board_type)
    {
        $this->board_types = $this->getBoardTypes($current_board_type);
    }

    private function getBoardTypes(string $current_board_type): array
    {
        return [
            [
                'label'       => dgettext('tuleap-taskboard', 'Cardwall (legacy, for backward compatibility)'),
                'value'       => TaskboardUsage::OPTION_CARDWALL,
                'is_selected' => $current_board_type === 'cardwall'
            ], [
                'label'       => dgettext('tuleap-taskboard', 'Taskboard (new, recommended)'),
                'value'       => TaskboardUsage::OPTION_TASKBOARD,
                'is_selected' => $current_board_type === 'taskboard'
            ], [
                'label'       => dgettext('tuleap-taskboard', 'Cardwall & Taskboard'),
                'value'       => TaskboardUsage::OPTION_CARDWALL_AND_TASKBOARD,
                'is_selected' => $current_board_type === 'both'
            ]
        ];
    }
}
