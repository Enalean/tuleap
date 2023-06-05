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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\CollectionOfAlphaNumericValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\MySelfIsNotSupportedException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NowIsNotSupportedException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\StatusOpenIsNotSupportedException;

final class FloatFieldChecker implements InvalidFieldChecker
{
    /**
     * @var EmptyStringChecker
     */
    private $empty_string_checker;

    /**
     * @var CollectionOfAlphaNumericValuesExtractor
     */
    private $values_extractor;

    public function __construct(
        EmptyStringChecker $empty_string_checker,
        CollectionOfAlphaNumericValuesExtractor $values_extractor,
    ) {
        $this->empty_string_checker = $empty_string_checker;
        $this->values_extractor     = $values_extractor;
    }

    public function checkFieldIsValidForComparison(Comparison $comparison, Tracker_FormElement_Field $field): void
    {
        try {
            $values = $this->values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);
        } catch (NowIsNotSupportedException $exception) {
            throw new FloatToNowComparisonException($field);
        } catch (MySelfIsNotSupportedException $exception) {
            throw new FloatToMySelfComparisonException($field);
        } catch (StatusOpenIsNotSupportedException $exception) {
            throw new FloatToStatusOpenComparisonException($field);
        }

        foreach ($values as $value) {
            if ($this->empty_string_checker->isEmptyStringAProblem((string) $value)) {
                throw new FloatToEmptyStringTermException($comparison, $field);
            }

            if (! is_numeric($value) && $value !== "") {
                throw new FloatToStringComparisonException($field, $value);
            }
        }
    }
}
