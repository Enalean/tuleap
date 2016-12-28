<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;

class QueryBuilder implements Visitor
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory)
    {
        $this->formelement_factory = $formelement_factory;
    }

    public function visitComparison(Comparison $comparison, PFUser $user, Tracker $tracker)
    {
        $comparison_value = $comparison->getValue();
        $formelement      = $this->formelement_factory->getUsedFieldByName($tracker->getId(), $comparison->getField());

        $from  = $formelement->getExpertFrom($comparison_value['literal'], spl_object_hash($comparison));
        $where = $formelement->getExpertWhere(spl_object_hash($comparison));

        return new FromWhere($from, $where);
    }

    public function visitAndExpression(AndExpression $and_expression, PFUser $user, Tracker $tracker)
    {
        $from_where_expression = $and_expression->getExpression()->accept($this, $user, $tracker);

        $tail = $and_expression->getTail();

        return $this->buildAndClause($user, $tracker, $tail, $from_where_expression);
    }

    public function visitOrExpression(OrExpression $or_expression, PFUser $user, Tracker $tracker)
    {
        $from_where_expression = $or_expression->getExpression()->accept($this, $user, $tracker);

        $tail = $or_expression->getTail();

        return $this->buildOrClause($user, $tracker, $tail, $from_where_expression);
    }

    public function visitOrOperand(OrOperand $or_operand, PFUser $user, Tracker $tracker)
    {
        $from_where_expression = $or_operand->getOperand()->accept($this, $user, $tracker);

        $tail = $or_operand->getTail();

        return $this->buildOrClause($user, $tracker, $tail, $from_where_expression);
    }

    public function visitAndOperand(AndOperand $and_operand, PFUser $user, Tracker $tracker)
    {
        $from_where_expression = $and_operand->getOperand()->accept($this, $user, $tracker);

        $tail = $and_operand->getTail();

        return $this->buildAndClause($user, $tracker, $tail, $from_where_expression);
    }

    private function buildAndClause(PFUser $user, Tracker $tracker, $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->accept($this, $user, $tracker);

        return new FromWhere(
            $from_where_expression->getFrom() . ' ' . $from_where_tail->getFrom(),
            $from_where_expression->getWhere() . ' AND ' . $from_where_tail->getWhere()
        );
    }

    private function buildOrClause(PFUser $user, Tracker $tracker, $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->accept($this, $user, $tracker);

        return new FromWhere(
            $from_where_expression->getFrom() . ' ' . $from_where_tail->getFrom(),
            '(' . $from_where_expression->getWhere() . ' OR ' . $from_where_tail->getWhere() . ')'
        );
    }
}
