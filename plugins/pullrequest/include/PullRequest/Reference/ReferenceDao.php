<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reference;

use Tuleap\DB\DataAccessObject;

class ReferenceDao extends DataAccessObject
{
    public function searchByScopeAndKeywordAndGroupId($scope, $keyword, $project_id)
    {
        $sql = 'SELECT *
                FROM reference
                LEFT JOIN reference_group ON (reference.id = reference_group.reference_id)
                WHERE scope = ?
                    AND reference.keyword = ?
                    AND group_id = ?
                    AND reference.id != 100';

        return $this->getDB()->run($sql, $scope, $keyword, $project_id);
    }
}
