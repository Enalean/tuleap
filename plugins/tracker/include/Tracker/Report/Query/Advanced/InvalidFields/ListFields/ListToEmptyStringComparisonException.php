<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use RuntimeException;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NoVisitorParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;

class ListToEmptyStringComparisonException extends InvalidFieldException implements Visitor
{
    public function __construct(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $message = sprintf(
            $comparison->accept($this, new NoVisitorParameters()),
            $field->getName()
        );
        parent::__construct($message);
    }

    public function visitEqualComparison(EqualComparison $comparison, NoVisitorParameters $parameters)
    {
        throw new RuntimeException('List values should be comparable = to an empty string');
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, NoVisitorParameters $parameters)
    {
        throw new RuntimeException('List values should be comparable != to an empty string');
    }

    public function visitInComparison(InComparison $comparison, NoVisitorParameters $parameters)
    {
        return dgettext("tuleap-tracker", "The list field '%s' cannot be compared to the empty string with IN() operator.");
    }
}
