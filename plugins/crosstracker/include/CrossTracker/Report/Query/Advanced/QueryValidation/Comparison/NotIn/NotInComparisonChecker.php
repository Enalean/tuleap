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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotIn;

use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;

class NotInComparisonChecker extends ComparisonChecker
{
    private const OPERATOR = 'NOT IN()';

    public function visitInValueWrapper(InValueWrapper $value_wrapper, $parameters)
    {
        $values = $value_wrapper->getValueWrappers();
        foreach ($values as $value) {
            $this->list_value_validator->checkValueIsValid($value->getValue());
        }
    }

    public function getOperator(): string
    {
        return self::OPERATOR;
    }
}
