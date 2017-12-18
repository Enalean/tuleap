<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\SearchableVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\SearchableVisitorParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Semantic\EqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Semantic\NotEqualComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NoVisitorParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;
use Tuleap\Tracker\Report\Query\AndFromWhere;
use Tuleap\Tracker\Report\Query\OrFromWhere;

class QueryBuilderVisitor implements Visitor
{
    /** @var EqualComparisonFromWhereBuilder */
    private $equal_comparison_from_where_builder;
    /** @var SearchableVisitor */
    private $searchable_visitor;
    /** @var NotEqualComparisonFromWhereBuilder */
    private $not_equal_comparison_from_where_builder;

    public function __construct(
        SearchableVisitor $searchable_visitor,
        EqualComparisonFromWhereBuilder $equal_comparison_from_where_builder,
        NotEqualComparisonFromWhereBuilder $not_equal_comparison_from_where_builder
    ) {
        $this->searchable_visitor                      = $searchable_visitor;
        $this->equal_comparison_from_where_builder     = $equal_comparison_from_where_builder;
        $this->not_equal_comparison_from_where_builder = $not_equal_comparison_from_where_builder;
    }

    public function buildFromWhere(Visitable $parsed_query)
    {
        return $parsed_query->accept($this, new NoVisitorParameters());
    }

    public function visitEqualComparison(EqualComparison $comparison, NoVisitorParameters $parameters)
    {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->equal_comparison_from_where_builder
            )
        );
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, NoVisitorParameters $parameters)
    {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->not_equal_comparison_from_where_builder
            )
        );
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, NoVisitorParameters $parameters)
    {
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, NoVisitorParameters $parameters)
    {
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, NoVisitorParameters $parameters)
    {
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, NoVisitorParameters $parameters)
    {
    }

    public function visitBetweenComparison(BetweenComparison $comparison, NoVisitorParameters $parameters)
    {
    }

    public function visitInComparison(InComparison $comparison, NoVisitorParameters $parameters)
    {
    }

    public function visitNotInComparison(NotInComparison $comparison, NoVisitorParameters $parameters)
    {
    }

    public function visitAndExpression(AndExpression $and_expression, NoVisitorParameters $parameters)
    {
        $from_where_expression = $and_expression->getExpression()->accept($this, $parameters);

        $tail = $and_expression->getTail();

        return $this->buildAndClause($parameters, $tail, $from_where_expression);
    }

    public function visitOrExpression(OrExpression $or_expression, NoVisitorParameters $parameters)
    {
        $from_where_expression = $or_expression->getExpression()->accept($this, $parameters);

        $tail = $or_expression->getTail();

        return $this->buildOrClause($parameters, $tail, $from_where_expression);
    }

    public function visitOrOperand(OrOperand $or_operand, NoVisitorParameters $parameters)
    {
        $from_where_expression = $or_operand->getOperand()->accept($this, $parameters);

        $tail = $or_operand->getTail();

        return $this->buildOrClause($parameters, $tail, $from_where_expression);
    }

    public function visitAndOperand(AndOperand $and_operand, NoVisitorParameters $parameters)
    {
        $from_where_expression = $and_operand->getOperand()->accept($this, $parameters);

        $tail = $and_operand->getTail();

        return $this->buildAndClause($parameters, $tail, $from_where_expression);
    }

    private function buildAndClause(NoVisitorParameters $parameters, $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->accept($this, $parameters);

        return new AndFromWhere($from_where_expression, $from_where_tail);
    }

    private function buildOrClause(NoVisitorParameters $parameters, $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->accept($this, $parameters);

        return new OrFromWhere($from_where_expression, $from_where_tail);
    }
}
