<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\InComparison;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ListReadOnlyConditionBuilder;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use UserManager;

final class ForSubmittedBy implements ListReadOnlyConditionBuilder
{
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        UserManager $user_manager,
    ) {
        $this->user_manager = $user_manager;
    }

    public function getCondition(array $values): ParametrizedSQLFragment
    {
        $in = EasyStatement::open()->in('?*', $this->getUsersIdByUserNames($values));

        $condition = "artifact.submitted_by IN($in)";

        return new ParametrizedSQLFragment($condition, $in->values());
    }

    private function getUsersIdByUserNames($values)
    {
        $users_id = [];
        foreach ($values as $value) {
            $users_id[] = $this->user_manager->getUserByUserName($value)->getId();
        }
        return $users_id;
    }
}
