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

namespace Tuleap\ProgramManagement\Adapter\Program\Hierarchy;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Program\Hierarchy\HierarchyAnalyzer;
use Tuleap\ProgramManagement\ProgramTracker;

final class ProgramManagementHierarchyDAO extends DataAccessObject implements HierarchyAnalyzer
{
    public function isPartOfAHierarchy(ProgramTracker $tracker_data): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM tracker_hierarchy
                JOIN plugin_program_management_plan ON (plugin_program_management_plan.plannable_tracker_id = tracker_hierarchy.parent_id)
                WHERE plugin_program_management_plan.plannable_tracker_id = ? OR tracker_hierarchy.child_id = ?';

        return $this->getDB()->exists($sql, $tracker_data->getTrackerId(), $tracker_data->getTrackerId());
    }
}
