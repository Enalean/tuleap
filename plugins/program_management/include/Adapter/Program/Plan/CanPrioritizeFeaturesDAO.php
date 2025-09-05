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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RetrieveProjectUgroupsCanPrioritizeItems;

final class CanPrioritizeFeaturesDAO extends DataAccessObject implements RetrieveProjectUgroupsCanPrioritizeItems
{
    /**
     * @return int[]
     */
    #[\Override]
    public function searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID(int $project_id): array
    {
        $sql = 'SELECT user_group_id
                FROM plugin_program_management_can_prioritize_features
                WHERE project_id = ?';

        return $this->getDB()->first($sql, $project_id);
    }
}
