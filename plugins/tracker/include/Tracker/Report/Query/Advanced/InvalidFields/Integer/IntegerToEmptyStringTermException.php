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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer;

use RuntimeException;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NoVisitorParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parenthesis;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\TermVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithForwardLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutForwardLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutReverseLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithReverseLink;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;

/**
 * @template-implements TermVisitor<NoVisitorParameters, string>
 */
final class IntegerToEmptyStringTermException extends InvalidFieldException implements TermVisitor
{
    public function __construct(Comparison $comparison, TrackerField $field)
    {
        $message = sprintf(
            $comparison->acceptTermVisitor($this, new NoVisitorParameters()),
            $field->getName()
        );
        parent::__construct($message);
    }

    #[\Override]
    public function visitEqualComparison(EqualComparison $comparison, $parameters)
    {
        throw new RuntimeException('Integer should be comparable = to an empty string');
    }

    #[\Override]
    public function visitNotEqualComparison(NotEqualComparison $comparison, $parameters)
    {
        throw new RuntimeException('Integer should be comparable != to an empty string');
    }

    #[\Override]
    public function visitLesserThanComparison(LesserThanComparison $comparison, $parameters)
    {
        return dgettext('tuleap-tracker', "The integer field '%s' cannot be compared to the empty string with < operator.");
    }

    #[\Override]
    public function visitGreaterThanComparison(GreaterThanComparison $comparison, $parameters)
    {
        return dgettext('tuleap-tracker', "The integer field '%s' cannot be compared to the empty string with > operator.");
    }

    #[\Override]
    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, $parameters)
    {
        return dgettext('tuleap-tracker', "The integer field '%s' cannot be compared to the empty string with <= operator.");
    }

    #[\Override]
    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, $parameters)
    {
        return dgettext('tuleap-tracker', "The integer field '%s' cannot be compared to the empty string with >= operator.");
    }

    #[\Override]
    public function visitBetweenComparison(BetweenComparison $comparison, $parameters)
    {
        return dgettext('tuleap-tracker', "The integer field '%s' cannot be compared to the empty string with BETWEEN() operator.");
    }

    #[\Override]
    public function visitInComparison(InComparison $comparison, $parameters)
    {
        throw new RuntimeException("The integer field '%s' is not supposed to be used with IN operator.");
    }

    #[\Override]
    public function visitNotInComparison(NotInComparison $comparison, $parameters)
    {
        throw new RuntimeException("The integer field '%s' is not supposed to be used with NOT IN operator.");
    }

    #[\Override]
    public function visitParenthesis(Parenthesis $parenthesis, $parameters)
    {
        throw new RuntimeException('We should not end up here.');
    }

    #[\Override]
    public function visitWithReverseLink(WithReverseLink $condition, $parameters)
    {
        throw new RuntimeException("The integer field '%s' is not supposed to be used with WITH PARENT operator.");
    }

    #[\Override]
    public function visitWithoutReverseLink(WithoutReverseLink $condition, $parameters)
    {
        throw new RuntimeException("The integer field '%s' is not supposed to be used with WITHOUT PARENT operator.");
    }

    #[\Override]
    public function visitWithForwardLink(WithForwardLink $condition, $parameters)
    {
        throw new RuntimeException("The integer field '%s' is not supposed to be used with WITH CHILDREN operator.");
    }

    #[\Override]
    public function visitWithoutForwardLink(WithoutForwardLink $condition, $parameters)
    {
        throw new RuntimeException("The integer field '%s' is not supposed to be used with WITHOUT CHILDREN operator.");
    }
}
