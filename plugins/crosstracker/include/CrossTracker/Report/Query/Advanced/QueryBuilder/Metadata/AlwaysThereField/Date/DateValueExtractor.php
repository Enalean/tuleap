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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date;

use RuntimeException;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NoValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;

/**
 * @template-implements ValueWrapperVisitor<NoValueWrapperParameters, string | int | float | array{min_value: string | int | float, max_value: string | int | float}>
 */
class DateValueExtractor implements ValueWrapperVisitor
{
    /**
     * @return string | int | float | array{min_value: string | int | float, max_value: string | int | float}
     */
    public function getValue(Comparison $comparison)
    {
        return $comparison->getValueWrapper()->accept($this, new NoValueWrapperParameters());
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        return $value_wrapper->getValue();
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        return $value_wrapper->getValue()->format(DateFormat::DATETIME);
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        $min = $value_wrapper->getMinValue()->accept($this, $parameters);
        if (is_array($min)) {
            throw new \Exception("Unsupported between value");
        }

        $max = $value_wrapper->getMaxValue()->accept($this, $parameters);
        if (is_array($max)) {
            throw new \Exception("Unsupported between value");
        }

        return [
            'min_value' => $min,
            'max_value' => $max,
        ];
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new RuntimeException('Should not end there');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new RuntimeException('Should not end there');
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new RuntimeException('Should not end there');
    }
}
