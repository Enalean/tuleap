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

namespace Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic;

use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToEmptyStringException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToStringException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;

abstract class GreaterOrLesserThanComparisonChecker extends ComparisonChecker
{
    const OPERATOR = '';

    /**
     * @param Metadata $metadata
     * @param Comparison $comparison
     * @throws InvalidSemanticComparisonException
     */
    public function checkComparisonIsValid(Metadata $metadata, Comparison $comparison)
    {
        if ($metadata->getName() !== AllowedMetadata::SUBMITTED_ON) {
            throw new OperatorNotAllowedForMetadataException($metadata, static::OPERATOR);
        }

        try {
            $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters($metadata));
        } catch (DateToEmptyStringException $e) {
            throw new DateToEmptyStringComparisonException($metadata, static::OPERATOR);
        } catch (DateToStringException $e) {
            throw new DateToStringComparisonException($metadata, $comparison->getValueWrapper()->getValue());
        }
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        $date_validator = new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME);
        $date_validator->checkValueIsValid($value_wrapper->getValue());
    }
}
