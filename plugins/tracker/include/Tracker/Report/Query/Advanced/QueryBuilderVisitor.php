<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

use Tracker;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\NotEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\LesserThanComparisonVisitor;

class QueryBuilderVisitor implements Visitor
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var NotEqualComparisonVisitor
     */
    private $not_equal_comparison_visitor;
    /**
     * @var EqualComparisonVisitor
     */
    private $equal_comparison_visitor;
    /**
     * @var LesserThanComparisonVisitor
     */
    private $lesser_than_comparison_visitor;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        EqualComparisonVisitor $equal_comparison_visitor,
        NotEqualComparisonVisitor $not_equal_comparison_visitor,
        LesserThanComparisonVisitor $lesser_than_comparison_visitor
    ) {
        $this->formelement_factory            = $formelement_factory;
        $this->equal_comparison_visitor       = $equal_comparison_visitor;
        $this->not_equal_comparison_visitor   = $not_equal_comparison_visitor;
        $this->lesser_than_comparison_visitor = $lesser_than_comparison_visitor;
    }

    public function buildFromWhere(Visitable $parsed_query, Tracker $tracker)
    {
        return $parsed_query->accept($this, new QueryBuilderParameters($tracker));
    }

    public function visitEqualComparison(EqualComparison $comparison, QueryBuilderParameters $parameters)
    {
        $formelement = $this->getFormElementFromComparison($comparison, $parameters);

        return $this->equal_comparison_visitor
            ->getFromWhereBuilder($formelement)
            ->getFromWhere($comparison, $formelement);
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, QueryBuilderParameters $parameters)
    {
        $formelement = $this->getFormElementFromComparison($comparison, $parameters);

        return $this->not_equal_comparison_visitor
            ->getFromWhereBuilder($formelement)
            ->getFromWhere($comparison, $formelement);
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, QueryBuilderParameters $parameters)
    {
        $formelement = $this->getFormElementFromComparison($comparison, $parameters);

        return $this->lesser_than_comparison_visitor
            ->getFromWhereBuilder($formelement)
            ->getFromWhere($comparison, $formelement);
    }

    public function visitAndExpression(AndExpression $and_expression, QueryBuilderParameters $parameters)
    {
        $from_where_expression = $and_expression->getExpression()->accept($this, $parameters);

        $tail = $and_expression->getTail();

        return $this->buildAndClause($parameters, $tail, $from_where_expression);
    }

    public function visitOrExpression(OrExpression $or_expression, QueryBuilderParameters $parameters)
    {
        $from_where_expression = $or_expression->getExpression()->accept($this, $parameters);

        $tail = $or_expression->getTail();

        return $this->buildOrClause($parameters, $tail, $from_where_expression);
    }

    public function visitOrOperand(OrOperand $or_operand, QueryBuilderParameters $parameters)
    {
        $from_where_expression = $or_operand->getOperand()->accept($this, $parameters);

        $tail = $or_operand->getTail();

        return $this->buildOrClause($parameters, $tail, $from_where_expression);
    }

    public function visitAndOperand(AndOperand $and_operand, QueryBuilderParameters $parameters)
    {
        $from_where_expression = $and_operand->getOperand()->accept($this, $parameters);

        $tail = $and_operand->getTail();

        return $this->buildAndClause($parameters, $tail, $from_where_expression);
    }

    private function buildAndClause(QueryBuilderParameters $parameters, $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->accept($this, $parameters);

        return new FromWhere(
            $from_where_expression->getFrom() . ' ' . $from_where_tail->getFrom(),
            $from_where_expression->getWhere() . ' AND ' . $from_where_tail->getWhere()
        );
    }

    private function buildOrClause(QueryBuilderParameters $parameters, $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->accept($this, $parameters);

        return new FromWhere(
            $from_where_expression->getFrom() . ' ' . $from_where_tail->getFrom(),
            '(' . $from_where_expression->getWhere() . ' OR ' . $from_where_tail->getWhere() . ')'
        );
    }

    private function getFormElementFromComparison(Comparison $comparison, QueryBuilderParameters $parameters)
    {
        $formelement = $this->formelement_factory->getUsedFieldByName(
            $parameters->getTracker()->getId(),
            $comparison->getField()
        );

        return $formelement;
    }
}
