<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Builders;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBFactory;

final class DatabaseBuilder
{
    private EasyDB $db;

    public function __construct()
    {
        $this->db = DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function buildPlanning(int $project_id, int $tracker_id): void
    {
        $this->db->insert(
            'plugin_agiledashboard_planning',
            [
                'name'                => "release plan",
                'group_id'            => $project_id,
                'planning_tracker_id' => $tracker_id,
                'backlog_title'       => "backlog",
            ]
        );
    }

    public function buildHierarchy(int $parent_id, int $child_id): void
    {
        $this->db->insert(
            'tracker_hierarchy',
            [
                'parent_id' => $parent_id,
                'child_id'  => $child_id,
            ],
        );
    }
}
