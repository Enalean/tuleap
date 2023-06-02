<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Users;

use ParagonIE\EasyDB\EasyStatement;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\ListValueExtractor;
use Tuleap\CrossTracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use UserManager;

final class InComparisonFromWhereBuilder implements FromWhereBuilder
{
    /** @var ListValueExtractor */
    private $extractor;

    /** @var UserManager */
    private $user_manager;

    /** @var string */
    private $alias_field;

    /**
     *
     * @param string $alias_field
     */
    public function __construct(
        ListValueExtractor $extractor,
        UserManager $user_manager,
        $alias_field,
    ) {
        $this->extractor    = $extractor;
        $this->user_manager = $user_manager;
        $this->alias_field  = $alias_field;
    }

    /**
     * @param Tracker[] $trackers
     * @return IProvideParametrizedFromAndWhereSQLFragments
     */
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers)
    {
        $values       = $this->extractor->extractCollectionOfValues($comparison);
        $in_condition = EasyStatement::open()->in(
            "{$this->alias_field} IN (?*)",
            $this->getUserIdsByUserNames($values)
        );

        return new ParametrizedFromWhere('', $in_condition, [], $in_condition->values());
    }

    private function getUserIdsByUserNames($values)
    {
        $user_ids = [];
        foreach ($values as $username) {
            $user_ids[] = $this->user_manager->getUserByUserName($username)->getId();
        }

        return $user_ids;
    }
}
