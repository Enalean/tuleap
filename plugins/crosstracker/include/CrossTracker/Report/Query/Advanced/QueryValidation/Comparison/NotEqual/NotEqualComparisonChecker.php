<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotEqual;

use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\OperatorToNowComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;

class NotEqualComparisonChecker extends ComparisonChecker
{
    private const OPERATOR = '!=';

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        $metadata = $parameters->getMetadata();
        if (in_array($metadata->getName(), AllowedMetadata::DATES)) {
            throw new OperatorToNowComparisonException($metadata, $this->getOperator());
        }

        parent::visitCurrentDateTimeValueWrapper($value_wrapper, $parameters);
    }

    public function getOperator(): string
    {
        return self::OPERATOR;
    }
}
