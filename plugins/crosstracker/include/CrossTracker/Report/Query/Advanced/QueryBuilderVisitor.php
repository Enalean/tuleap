<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use PFUser;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ForwardLinkFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ReverseLinkFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\FromWhereSearchableVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\FromWhereSearchableVisitorParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parenthesis;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\TermVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithForwardLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutForwardLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutReverseLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithReverseLink;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedAndFromWhere;
use Tuleap\Tracker\Report\Query\ParametrizedOrFromWhere;

/**
 * @template-implements LogicalVisitor<QueryBuilderVisitorParameters, IProvideParametrizedFromAndWhereSQLFragments>
 * @template-implements TermVisitor<QueryBuilderVisitorParameters, IProvideParametrizedFromAndWhereSQLFragments>
 */
final readonly class QueryBuilderVisitor implements LogicalVisitor, TermVisitor
{
    public function __construct(
        private FromWhereSearchableVisitor $searchable_visitor,
        private ReverseLinkFromWhereBuilder $reverse_link_from_where_builder,
        private ForwardLinkFromWhereBuilder $forward_link_from_where_builder,
        private Field\FieldFromWhereBuilder $field_from_where_builder,
        private Metadata\MetadataFromWhereBuilder $metadata_from_where_builder,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function buildFromWhere(
        Logical $parsed_query,
        array $trackers,
        PFUser $user,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        return $parsed_query->acceptLogicalVisitor($this, new QueryBuilderVisitorParameters($trackers, $user));
    }

    public function visitEqualComparison(EqualComparison $comparison, $parameters)
    {
        return $this->visitComparison($comparison, $parameters);
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, $parameters)
    {
        return $this->visitComparison($comparison, $parameters);
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, $parameters)
    {
        return $this->visitComparison($comparison, $parameters);
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, $parameters)
    {
        return $this->visitComparison($comparison, $parameters);
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, $parameters)
    {
        return $this->visitComparison($comparison, $parameters);
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, $parameters)
    {
        return $this->visitComparison($comparison, $parameters);
    }

    public function visitBetweenComparison(BetweenComparison $comparison, $parameters)
    {
        return $this->visitComparison($comparison, $parameters);
    }

    public function visitInComparison(InComparison $comparison, $parameters)
    {
        return $this->visitComparison($comparison, $parameters);
    }

    public function visitNotInComparison(NotInComparison $comparison, $parameters)
    {
        return $this->visitComparison($comparison, $parameters);
    }

    private function visitComparison(Comparison $comparison, QueryBuilderVisitorParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameters(
                $comparison,
                $this->metadata_from_where_builder,
                $this->field_from_where_builder,
                $parameters->user,
                $parameters->trackers,
            ),
        );
    }

    public function visitParenthesis(Parenthesis $parenthesis, $parameters)
    {
        return $parenthesis->or_expression->acceptLogicalVisitor($this, $parameters);
    }

    public function visitAndExpression(AndExpression $and_expression, $parameters)
    {
        $from_where_expression = $and_expression->getExpression()->acceptTermVisitor($this, $parameters);

        $tail = $and_expression->getTail();

        return $this->buildAndClause($parameters, $tail, $from_where_expression);
    }

    public function visitOrExpression(OrExpression $or_expression, $parameters)
    {
        $from_where_expression = $or_expression->getExpression()->acceptLogicalVisitor($this, $parameters);

        $tail = $or_expression->getTail();

        return $this->buildOrClause($parameters, $tail, $from_where_expression);
    }

    public function visitOrOperand(OrOperand $or_operand, $parameters)
    {
        $from_where_expression = $or_operand->getOperand()->acceptLogicalVisitor($this, $parameters);

        $tail = $or_operand->getTail();

        return $this->buildOrClause($parameters, $tail, $from_where_expression);
    }

    public function visitAndOperand(AndOperand $and_operand, $parameters)
    {
        $from_where_expression = $and_operand->getOperand()->acceptTermVisitor($this, $parameters);

        $tail = $and_operand->getTail();

        return $this->buildAndClause($parameters, $tail, $from_where_expression);
    }

    private function buildAndClause(
        QueryBuilderVisitorParameters $parameters,
        OrOperand|AndOperand|null $tail,
        $from_where_expression,
    ) {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->acceptLogicalVisitor($this, $parameters);

        return new ParametrizedAndFromWhere($from_where_expression, $from_where_tail);
    }

    private function buildOrClause(
        QueryBuilderVisitorParameters $parameters,
        OrOperand|AndOperand|null $tail,
        $from_where_expression,
    ) {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->acceptLogicalVisitor($this, $parameters);

        return new ParametrizedOrFromWhere($from_where_expression, $from_where_tail);
    }

    public function visitWithReverseLink(WithReverseLink $condition, $parameters)
    {
        return $this->reverse_link_from_where_builder->getFromWhereForWithReverseLink($condition, $parameters->user);
    }

    public function visitWithoutReverseLink(WithoutReverseLink $condition, $parameters)
    {
        return $this->reverse_link_from_where_builder->getFromWhereForWithoutReverseLink($condition, $parameters->user);
    }

    public function visitWithForwardLink(WithForwardLink $condition, $parameters)
    {
        return $this->forward_link_from_where_builder->getFromWhereForWithForwardLink($condition, $parameters->user);
    }

    public function visitWithoutForwardLink(WithoutForwardLink $condition, $parameters)
    {
        return $this->forward_link_from_where_builder->getFromWhereForWithoutForwardLink($condition, $parameters->user);
    }
}
