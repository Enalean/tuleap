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

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

use PFUser;
use Tracker;

class DepthValidatorVisitor implements Visitor
{
    private $limit;

    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    public function visitComparison(Comparison $comparison, DepthValidatorParameters $parameters)
    {
        return 1;
    }

    public function visitAndExpression(AndExpression $and_expression, DepthValidatorParameters $parameters)
    {
        return $this->visitExpression($and_expression, $parameters);
    }

    public function visitOrExpression(OrExpression $or_expression, DepthValidatorParameters $parameters)
    {
        return $this->visitExpression($or_expression, $parameters);
    }

    public function visitOrOperand(OrOperand $or_operand, DepthValidatorParameters $parameters)
    {
        return $this->visitOperand($or_operand, $parameters);
    }

    public function visitAndOperand(AndOperand $and_operand, DepthValidatorParameters $parameters)
    {
        return $this->visitOperand($and_operand, $parameters);
    }

    private function visitTail($tail, DepthValidatorParameters $parameters)
    {
        if ($tail) {
            return $tail->accept($this, $parameters);
        } else {
            return 0;
        }
    }

    private function visitOperand($operand, DepthValidatorParameters $parameters)
    {
        $left = $operand->getOperand()->accept($this, $parameters);
        $right = $this->visitTail($operand->getTail(), $parameters);

        $depth = $left > $right ? $left + 1 : $right + 1;
        if ($this->isDepthExceed($depth)) {
            throw new LimitDepthIsExceededException();
        }

        return $depth;
    }

    private function visitExpression($expression, DepthValidatorParameters $parameters)
    {
        $left = $expression->getExpression()->accept($this, $parameters);
        $right = $this->visitTail($expression->getTail(), $parameters);

        $depth = $left > $right ? $left + 1 : $right + 1;
        if ($this->isDepthExceed($depth)) {
            throw new LimitDepthIsExceededException();
        }

        return $depth;
    }

    private function isDepthExceed($depth)
    {
        if (! $this->limit) {
            return false;
        }

        return $depth > $this->limit;
    }
}
