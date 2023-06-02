<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Logical;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LogicalVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;

/**
 * @template-implements LogicalVisitor<SizeValidatorParameters, void>
 * @template-implements ComparisonVisitor<SizeValidatorParameters, void>
 */
final class SizeValidatorVisitor implements LogicalVisitor, ComparisonVisitor
{
    private $limit;

    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    public function checkSizeOfTree(Logical $parsed_query): void
    {
        $parsed_query->acceptLogicalVisitor($this, new SizeValidatorParameters(0));
    }

    public function visitEqualComparison(EqualComparison $comparison, $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitBetweenComparison(BetweenComparison $comparison, $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitInComparison(InComparison $comparison, $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitNotInComparison(NotInComparison $comparison, $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitAndExpression(AndExpression $and_expression, $parameters)
    {
        $this->incrementSize($parameters);

        $and_expression->getExpression()->acceptComparisonVisitor($this, $parameters);

        $this->visitTail($and_expression->getTail(), $parameters);
    }

    public function visitOrExpression(OrExpression $or_expression, $parameters)
    {
        $this->incrementSize($parameters);

        $or_expression->getExpression()->acceptLogicalVisitor($this, $parameters);

        $this->visitTail($or_expression->getTail(), $parameters);
    }

    public function visitOrOperand(OrOperand $or_operand, $parameters)
    {
        $this->incrementSize($parameters);

        $or_operand->getOperand()->acceptLogicalVisitor($this, $parameters);

        $this->visitTail($or_operand->getTail(), $parameters);
    }

    public function visitAndOperand(AndOperand $and_operand, $parameters)
    {
        $this->incrementSize($parameters);

        $and_operand->getOperand()->acceptComparisonVisitor($this, $parameters);

        $this->visitTail($and_operand->getTail(), $parameters);
    }

    private function visitTail(OrOperand | AndOperand | null $tail, SizeValidatorParameters $parameters)
    {
        if ($tail) {
            $tail->acceptLogicalVisitor($this, $parameters);
        }
    }

    private function visitComparison($comparison, SizeValidatorParameters $parameters)
    {
        $this->incrementSize($parameters);
    }

    private function checkSize($size)
    {
        if ($this->isSizeExceed($size)) {
            throw new LimitSizeIsExceededException();
        }
    }

    private function isSizeExceed($size)
    {
        if (! $this->limit) {
            return false;
        }

        return $size > $this->limit;
    }

    private function incrementSize(SizeValidatorParameters $parameters)
    {
        $parameters->incrementSize();
        $this->checkSize($parameters->getSize());
    }
}
