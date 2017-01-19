<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;

class SizeValidatorVisitor implements Visitor
{
    private $limit;

    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    public function checkSizeOfTree(Visitable $parsed_query)
    {
        $parsed_query->accept($this, new SizeValidatorParameters(0));
    }

    public function visitEqualComparison(EqualComparison $comparison, SizeValidatorParameters $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, SizeValidatorParameters $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, SizeValidatorParameters $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, SizeValidatorParameters $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, SizeValidatorParameters $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, SizeValidatorParameters $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitBetweenComparison(BetweenComparison $comparison, SizeValidatorParameters $parameters)
    {
        $this->visitComparison($comparison, $parameters);
    }

    public function visitAndExpression(AndExpression $and_expression, SizeValidatorParameters $parameters)
    {
        $this->visitExpression($and_expression, $parameters);
    }

    public function visitOrExpression(OrExpression $or_expression, SizeValidatorParameters $parameters)
    {
        $this->visitExpression($or_expression, $parameters);
    }

    public function visitOrOperand(OrOperand $or_operand, SizeValidatorParameters $parameters)
    {
        $this->visitOperand($or_operand, $parameters);
    }

    public function visitAndOperand(AndOperand $and_operand, SizeValidatorParameters $parameters)
    {
        $this->visitOperand($and_operand, $parameters);
    }

    private function visitTail($tail, SizeValidatorParameters $parameters)
    {
        if ($tail) {
            $tail->accept($this, $parameters);
        }
    }

    private function visitOperand($operand, SizeValidatorParameters $parameters)
    {
        $this->incrementSize($parameters);

        $operand->getOperand()->accept($this, $parameters);

        $this->visitTail($operand->getTail(), $parameters);
    }

    private function visitExpression($expression, SizeValidatorParameters $parameters)
    {
        $this->incrementSize($parameters);

        $expression->getExpression()->accept($this, $parameters);

        $this->visitTail($expression->getTail(), $parameters);
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
