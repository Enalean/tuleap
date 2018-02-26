<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date;

use RuntimeException;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder;
use Tuleap\CrossTracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NoValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;

abstract class FromWhereBuilder implements QueryBuilder\Metadata\FromWhereBuilder, ValueWrapperVisitor
{
    /**
     * @param Metadata $metadata
     * @param Comparison $comparison
     * @param Tracker[] $trackers
     * @return IProvideParametrizedFromAndWhereSQLFragments
     */
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers)
    {
        return $comparison->getValueWrapper()->accept($this, new NoValueWrapperParameters());
    }

    /**
     * @return ParametrizedFromWhere
     */
    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $this->getParametrizedFromWhere(
            $value_wrapper->getValue()
        );
    }

    /**
     * @return ParametrizedFromWhere
     */
    public function visitCurrentDateTimeValueWrapper(
        CurrentDateTimeValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        return $this->getParametrizedFromWhere(
            $value_wrapper->getValue()->format(DateFormat::DATETIME)
        );
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        throw new RuntimeException('Should not end there');
    }

    public function visitInValueWrapper(
        InValueWrapper $collection_of_value_wrappers,
        ValueWrapperParameters $parameters
    ) {
        throw new RuntimeException('Should not end there');
    }

    public function visitCurrentUserValueWrapper(
        CurrentUserValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        throw new RuntimeException('Should not end there');
    }

    public function visitStatusOpenValueWrapper(
        StatusOpenValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        throw new RuntimeException('Should not end there');
    }

    /**
     * @param $value
     * @return ParametrizedFromWhere
     */
    abstract protected function getParametrizedFromWhere($value);
}
