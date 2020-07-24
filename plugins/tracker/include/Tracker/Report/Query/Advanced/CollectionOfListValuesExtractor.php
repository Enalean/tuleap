<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FieldValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListToMySelfForAnonymousComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListToNowComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListToStatusOpenComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\MySelfIsNotSupportedForAnonymousException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NowIsNotSupportedException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\StatusOpenIsNotSupportedException;

class CollectionOfListValuesExtractor implements ValueWrapperVisitor
{
    /** @return array */
    public function extractCollectionOfValues(ValueWrapper $value_wrapper, Tracker_FormElement_Field $field)
    {
        try {
            return (array) $value_wrapper->accept($this, new FieldValueWrapperParameters($field));
        } catch (NowIsNotSupportedException $exception) {
            throw new ListToNowComparisonException($field);
        } catch (MySelfIsNotSupportedForAnonymousException $exception) {
            throw new ListToMySelfForAnonymousComparisonException($field);
        } catch (StatusOpenIsNotSupportedException $exception) {
            throw new ListToStatusOpenComparisonException($field);
        }
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        throw new NowIsNotSupportedException();
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $value_wrapper->getValue();
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        $values   = [];
        $values[] = $value_wrapper->getMinValue()->accept($this, $parameters);
        $values[] = $value_wrapper->getMaxValue()->accept($this, $parameters);

        return $values;
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, ValueWrapperParameters $parameters)
    {
        $values = [];
        foreach ($collection_of_value_wrappers->getValueWrappers() as $value_wrapper) {
            $values[] = $value_wrapper->accept($this, $parameters);
        }

        return $values;
    }

    public function visitCurrentUserValueWrapper(
        CurrentUserValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        $value = $value_wrapper->getValue();
        if (! $value) {
            throw new MySelfIsNotSupportedForAnonymousException();
        }
        return $value;
    }

    public function visitStatusOpenValueWrapper(
        StatusOpenValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        throw new StatusOpenIsNotSupportedException();
    }
}
