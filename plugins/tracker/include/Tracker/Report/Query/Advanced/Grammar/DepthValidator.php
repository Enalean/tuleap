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

class DepthValidator implements Visitor
{
    private $limit;

    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    public function visitComparison(Comparison $comparison, PFUser $user, Tracker $tracker)
    {
        return 1;
    }

    public function visitAndExpression(AndExpression $and_expression, PFUser $user, Tracker $tracker)
    {
        return $this->visitExpression($and_expression, $user, $tracker);
    }

    public function visitOrExpression(OrExpression $or_expression, PFUser $user, Tracker $tracker)
    {
        return $this->visitExpression($or_expression, $user, $tracker);
    }

    public function visitOrOperand(OrOperand $or_operand, PFUser $user, Tracker $tracker)
    {
        return $this->visitOperand($or_operand, $user, $tracker);
    }

    public function visitAndOperand(AndOperand $and_operand, PFUser $user, Tracker $tracker)
    {
        return $this->visitOperand($and_operand, $user, $tracker);
    }

    private function visitTail($tail, PFUser $user, Tracker $tracker)
    {
        if ($tail) {
            return $tail->accept($this, $user, $tracker);
        } else {
            return 0;
        }
    }

    private function visitOperand($operand, PFUser $user, Tracker $tracker)
    {
        $left = $operand->getOperand()->accept($this, $user, $tracker);
        $right = $this->visitTail($operand->getTail(), $user, $tracker);

        $depth = $left > $right ? $left + 1 : $right + 1;
        if ($this->isDepthExceed($depth)) {
            throw new LimitDepthIsExceededException();
        }

        return $depth;
    }

    private function visitExpression($expression, PFUser $user, Tracker $tracker)
    {
        $left = $expression->getExpression()->accept($this, $user, $tracker);
        $right = $this->visitTail($expression->getTail(), $user, $tracker);

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
