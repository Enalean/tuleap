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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date;

use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;

final readonly class DateFieldChecker implements InvalidFieldChecker
{
    /**
     * @throws DateToEmptyStringTermException
     * @throws DateToMySelfComparisonException
     * @throws DateToStatusOpenComparisonException
     * @throws DateToStringComparisonException
     * @throws FieldIsNotSupportedForComparisonException
     */
    public function checkFieldIsValidForComparison(Comparison $comparison, \Tracker_FormElement_Field $field): void
    {
        assert($field instanceof \Tracker_FormElement_Field_Date);
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->checkDateValueIsValid($comparison, $field, false),
            ComparisonType::Between,
            ComparisonType::GreaterThan,
            ComparisonType::GreaterThanOrEqual,
            ComparisonType::LesserThan,
            ComparisonType::LesserThanOrEqual => $this->checkDateValueIsValid($comparison, $field, true),
            ComparisonType::In => throw new FieldIsNotSupportedForComparisonException($field, 'in()'),
            ComparisonType::NotIn => throw new FieldIsNotSupportedForComparisonException($field, 'not in()'),
        };
    }

    /**
     * @throws DateToEmptyStringTermException
     * @throws DateToMySelfComparisonException
     * @throws DateToStatusOpenComparisonException
     * @throws DateToStringComparisonException
     */
    private function checkDateValueIsValid(
        Comparison $comparison,
        \Tracker_FormElement_Field_Date $field,
        bool $is_empty_string_a_problem,
    ): void {
        $format           = ($field->isTimeDisplayed()) ? DateFormat::DATETIME : DateFormat::DATE;
        $empty_checker    = ($is_empty_string_a_problem) ? new EmptyStringForbidden() : new EmptyStringAllowed();
        $validator        = new DateFormatValidator($empty_checker, $format);
        $values_extractor = new CollectionOfDateValuesExtractor($format);

        $date_values = $values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);

        foreach ($date_values as $value) {
            try {
                $validator->checkValueIsValid($value);
            } catch (DateToEmptyStringException) {
                throw new DateToEmptyStringTermException($comparison, $field);
            } catch (DateToStringException) {
                throw new DateToStringComparisonException($field, $value);
            }
        }
    }
}
