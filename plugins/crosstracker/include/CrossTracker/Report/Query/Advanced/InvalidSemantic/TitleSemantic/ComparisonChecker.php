<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\TitleSemantic;

use DateTime;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\ICheckSemanticFieldForAComparison;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\TitleToDateComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;

class ComparisonChecker implements ICheckSemanticFieldForAComparison, ValueWrapperVisitor
{
    public function checkSemanticMetadataIsValid(Comparison $comparison)
    {
        $value = $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters());

        if (is_float($value + 0)) {
            throw new TitleToFloatComparisonException($value);
        }

        if (is_numeric($value)) {
            throw new TitleToIntComparisonException($value);
        }

        if ($this->getDateTimeFromValue($value) !== false && $value !== '') {
            throw new TitleToDateComparisonException();
        }
    }

    public function visitCurrentDateTimeValueWrapper(
        CurrentDateTimeValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        throw new TitleToNowComparisonException();
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $value_wrapper->getValue();
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        // Do nothing, EqualComparison should not receive a BetweenValueWrapper
    }

    public function visitInValueWrapper(
        InValueWrapper $collection_of_value_wrappers,
        ValueWrapperParameters $parameters
    ) {
        // Do nothing, EqualComparison should not receive a InValueWrapper
    }

    public function visitCurrentUserValueWrapper(
        CurrentUserValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        throw new TitleToMyselfComparisonException();
    }

    private function getDateTimeFromValue($value)
    {
        $date_value = DateTime::createFromFormat(DateFormat::DATETIME, $value);
        if ($date_value !== false) {
            return $date_value;
        }

        $date_value = DateTime::createFromFormat(DateFormat::DATE, $value);

        return $date_value;
    }
}
