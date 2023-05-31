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

use RuntimeException;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NoVisitorParameters;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;

/**
 * @template-implements ComparisonVisitor<NoVisitorParameters, string>
 */
class FloatToEmptyStringComparisonException extends InvalidFieldException implements ComparisonVisitor
{
    public function __construct(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $message = sprintf(
            $comparison->acceptComparisonVisitor($this, new NoVisitorParameters()),
            $field->getName()
        );
        parent::__construct($message);
    }

    public function visitEqualComparison(EqualComparison $comparison, $parameters)
    {
        throw new RuntimeException('Float should be comparable = to an empty string');
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, $parameters)
    {
        throw new RuntimeException('Float should be comparable != to an empty string');
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, $parameters)
    {
        return dgettext("tuleap-tracker", "The float field '%s' cannot be compared to the empty string with < operator.");
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, $parameters)
    {
        return dgettext("tuleap-tracker", "The float field '%s' cannot be compared to the empty string with > operator.");
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, $parameters)
    {
        return dgettext("tuleap-tracker", "The float field '%s' cannot be compared to the empty string with <= operator.");
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, $parameters)
    {
        return dgettext("tuleap-tracker", "The float field '%s' cannot be compared to the empty string with >= operator.");
    }

    public function visitBetweenComparison(BetweenComparison $comparison, $parameters)
    {
        return dgettext("tuleap-tracker", "The float field '%s' cannot be compared to the empty string with BETWEEN() operator.");
    }

    public function visitInComparison(InComparison $comparison, $parameters)
    {
        throw new RuntimeException("The float field '%s' is not supposed to be used with IN operator.");
    }

    public function visitNotInComparison(NotInComparison $comparison, $parameters)
    {
        throw new RuntimeException("The float field '%s' is not supposed to be used with NOT IN operator.");
    }
}
