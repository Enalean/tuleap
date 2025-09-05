<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FieldValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;

/**
 * @template-implements ValueWrapperVisitor<FieldValueWrapperParameters, void>
 */
final class FileFieldChecker implements ValueWrapperVisitor
{
    /**
     * @throws FieldIsNotSupportedForComparisonException
     * @throws FileToMySelfComparisonException
     * @throws FileToNowComparisonException
     * @throws FileToStatusOpenComparisonException
     */
    public function checkFieldIsValidForComparison(Comparison $comparison, \Tuleap\Tracker\FormElement\Field\TrackerField $field): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->checkFileValueIsValid($comparison, $field),
            default => throw new FieldIsNotSupportedForComparisonException($field, $comparison->getType()->value),
        };
    }

    /**
     * @throws FileToMySelfComparisonException
     * @throws FileToNowComparisonException
     * @throws FileToStatusOpenComparisonException
     */
    private function checkFileValueIsValid(Comparison $comparison, \Tuleap\Tracker\FormElement\Field\TrackerField $field): void
    {
        $comparison->getValueWrapper()->accept($this, new FieldValueWrapperParameters($field));
    }

    #[\Override]
    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        // Do nothing, SimpleValue is valid
    }

    #[\Override]
    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new FileToNowComparisonException($parameters->field);
    }

    #[\Override]
    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new FileToMySelfComparisonException($parameters->field);
    }

    #[\Override]
    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new FileToStatusOpenComparisonException($parameters->field);
    }

    #[\Override]
    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new \LogicException('Should not end there');
    }

    #[\Override]
    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new \LogicException('Should not end there');
    }
}
