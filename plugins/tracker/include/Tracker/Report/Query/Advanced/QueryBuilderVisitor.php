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

use Tracker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parenthesis;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\TermVisitor;
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutParent;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithParent;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ArtifactLinkFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\BetweenFieldComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualFieldComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\GreaterThanFieldComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\GreaterThanOrEqualFieldComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\InFieldComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\LesserThanFieldComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\LesserThanOrEqualFieldComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataBetweenComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataEqualComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataGreaterThanComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataGreaterThanOrEqualComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataInComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataLesserThanComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataLesserThanOrEqualComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataNotEqualComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataNotInComparisonFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\NotEqualFieldComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\NotInFieldComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereSearchableVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereSearchableVisitorParameter;
use Tuleap\Tracker\Report\Query\AndFromWhere;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\OrFromWhere;

/**
 * @template-implements LogicalVisitor<QueryBuilderParameters, IProvideFromAndWhereSQLFragments>
 * @template-implements TermVisitor<QueryBuilderParameters, IProvideFromAndWhereSQLFragments>
 */
final class QueryBuilderVisitor implements LogicalVisitor, TermVisitor
{
    /**
     * @var NotEqualFieldComparisonVisitor
     */
    private $not_equal_comparison_visitor;
    /**
     * @var EqualFieldComparisonVisitor
     */
    private $equal_comparison_visitor;
    /**
     * @var LesserThanFieldComparisonVisitor
     */
    private $lesser_than_comparison_visitor;
    /**
     * @var GreaterThanFieldComparisonVisitor
     */
    private $greater_than_comparison_visitor;
    /**
     * @var LesserThanOrEqualFieldComparisonVisitor
     */
    private $lesser_than_or_equal_comparison_visitor;
    /**
     * @var GreaterThanOrEqualFieldComparisonVisitor
     */
    private $greater_than_or_equal_comparison_visitor;
    /**
     * @var BetweenFieldComparisonVisitor
     */
    private $between_comparison_visitor;
    /**
     * @var InFieldComparisonVisitor
     */
    private $in_comparison_visitor;

    /**
     * @var NotInFieldComparisonVisitor
     */
    private $not_in_comparison_visitor;
    /**
     * @var MetadataEqualComparisonFromWhereBuilder
     */
    private $metadata_equal_comparison_from_where_builder;
    /**
     * @var MetadataNotEqualComparisonFromWhereBuilder
     */
    private $metadata_not_equal_comparison_from_where_builder;
    /**
     * @var MetadataLesserThanComparisonFromWhereBuilder
     */
    private $metadata_lesser_than_comparison_from_where_builder;
    /**
     * @var MetadataGreaterThanComparisonFromWhereBuilder
     */
    private $metadata_greater_than_comparison_from_where_builder;
    /**
     * @var MetadataLesserThanOrEqualComparisonFromWhereBuilder
     */
    private $metadata_lesser_than_or_equal_comparison_from_where_builder;
    /**
     * @var MetadataGreaterThanOrEqualComparisonFromWhereBuilder
     */
    private $metadata_greater_than_or_equal_comparison_from_where_builder;
    /**
     * @var MetadataBetweenComparisonFromWhereBuilder
     */
    private $metadata_between_comparison_from_where_builder;
    /**
     * @var MetadataInComparisonFromWhereBuilder
     */
    private $metadata_in_comparison_from_where_builder;
    /**
     * @var MetadataNotInComparisonFromWhereBuilder
     */
    private $metadata_not_in_comparison_from_where_builder;

    public function __construct(
        EqualFieldComparisonVisitor $equal_comparison_visitor,
        NotEqualFieldComparisonVisitor $not_equal_comparison_visitor,
        LesserThanFieldComparisonVisitor $lesser_than_comparison_visitor,
        GreaterThanFieldComparisonVisitor $superior_comparison_visitor,
        LesserThanOrEqualFieldComparisonVisitor $lesser_than_or_equal_comparison_visitor,
        GreaterThanOrEqualFieldComparisonVisitor $greater_than_or_equal_comparison_visitor,
        BetweenFieldComparisonVisitor $between_comparison_visitor,
        InFieldComparisonVisitor $in_comparison_visitor,
        NotInFieldComparisonVisitor $not_in_comparison_visitor,
        private readonly FromWhereSearchableVisitor $searchable_visitor,
        MetadataEqualComparisonFromWhereBuilder $metadata_equal_comparison_from_where_builder,
        MetadataNotEqualComparisonFromWhereBuilder $metadata_not_equal_comparison_from_where_builder,
        MetadataLesserThanComparisonFromWhereBuilder $metadata_lesser_than_comparison_from_where_builder,
        MetadataGreaterThanComparisonFromWhereBuilder $metadata_greater_than_comparison_from_where_builder,
        MetadataLesserThanOrEqualComparisonFromWhereBuilder $metadata_lesser_than_or_equal_comparison_from_where_builder,
        MetadataGreaterThanOrEqualComparisonFromWhereBuilder $metadata_greater_than_or_equal_comparison_from_where_builder,
        MetadataBetweenComparisonFromWhereBuilder $metadata_between_comparison_from_where_builder,
        MetadataInComparisonFromWhereBuilder $metadata_in_comparison_from_where_builder,
        MetadataNotInComparisonFromWhereBuilder $metadata_not_in_comparison_from_where_builder,
        private readonly ArtifactLinkFromWhereBuilder $artifact_link_from_where_builder,
    ) {
        $this->equal_comparison_visitor                                     = $equal_comparison_visitor;
        $this->not_equal_comparison_visitor                                 = $not_equal_comparison_visitor;
        $this->lesser_than_comparison_visitor                               = $lesser_than_comparison_visitor;
        $this->greater_than_comparison_visitor                              = $superior_comparison_visitor;
        $this->lesser_than_or_equal_comparison_visitor                      = $lesser_than_or_equal_comparison_visitor;
        $this->greater_than_or_equal_comparison_visitor                     = $greater_than_or_equal_comparison_visitor;
        $this->between_comparison_visitor                                   = $between_comparison_visitor;
        $this->in_comparison_visitor                                        = $in_comparison_visitor;
        $this->not_in_comparison_visitor                                    = $not_in_comparison_visitor;
        $this->metadata_equal_comparison_from_where_builder                 = $metadata_equal_comparison_from_where_builder;
        $this->metadata_not_equal_comparison_from_where_builder             = $metadata_not_equal_comparison_from_where_builder;
        $this->metadata_lesser_than_comparison_from_where_builder           = $metadata_lesser_than_comparison_from_where_builder;
        $this->metadata_greater_than_comparison_from_where_builder          = $metadata_greater_than_comparison_from_where_builder;
        $this->metadata_lesser_than_or_equal_comparison_from_where_builder  = $metadata_lesser_than_or_equal_comparison_from_where_builder;
        $this->metadata_greater_than_or_equal_comparison_from_where_builder = $metadata_greater_than_or_equal_comparison_from_where_builder;
        $this->metadata_between_comparison_from_where_builder               = $metadata_between_comparison_from_where_builder;
        $this->metadata_in_comparison_from_where_builder                    = $metadata_in_comparison_from_where_builder;
        $this->metadata_not_in_comparison_from_where_builder                = $metadata_not_in_comparison_from_where_builder;
    }

    public function buildFromWhere(Logical $parsed_query, Tracker $tracker, \PFUser $user)
    {
        return $parsed_query->acceptLogicalVisitor($this, new QueryBuilderParameters($tracker, $user));
    }

    public function visitEqualComparison(EqualComparison $comparison, $parameters)
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameter(
                $comparison,
                $this->equal_comparison_visitor,
                $parameters->tracker,
                $this->metadata_equal_comparison_from_where_builder
            )
        );
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, $parameters)
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameter(
                $comparison,
                $this->not_equal_comparison_visitor,
                $parameters->tracker,
                $this->metadata_not_equal_comparison_from_where_builder
            )
        );
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, $parameters)
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameter(
                $comparison,
                $this->lesser_than_comparison_visitor,
                $parameters->tracker,
                $this->metadata_lesser_than_comparison_from_where_builder
            )
        );
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, $parameters)
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameter(
                $comparison,
                $this->greater_than_comparison_visitor,
                $parameters->tracker,
                $this->metadata_greater_than_comparison_from_where_builder
            )
        );
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, $parameters)
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameter(
                $comparison,
                $this->lesser_than_or_equal_comparison_visitor,
                $parameters->tracker,
                $this->metadata_lesser_than_or_equal_comparison_from_where_builder
            )
        );
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, $parameters)
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameter(
                $comparison,
                $this->greater_than_or_equal_comparison_visitor,
                $parameters->tracker,
                $this->metadata_greater_than_or_equal_comparison_from_where_builder
            )
        );
    }

    public function visitBetweenComparison(BetweenComparison $comparison, $parameters)
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameter(
                $comparison,
                $this->between_comparison_visitor,
                $parameters->tracker,
                $this->metadata_between_comparison_from_where_builder
            )
        );
    }

    public function visitInComparison(InComparison $comparison, $parameters)
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameter(
                $comparison,
                $this->in_comparison_visitor,
                $parameters->tracker,
                $this->metadata_in_comparison_from_where_builder
            )
        );
    }

    public function visitNotInComparison(NotInComparison $comparison, $parameters)
    {
        return $comparison->getSearchable()->acceptSearchableVisitor(
            $this->searchable_visitor,
            new FromWhereSearchableVisitorParameter(
                $comparison,
                $this->not_in_comparison_visitor,
                $parameters->tracker,
                $this->metadata_not_in_comparison_from_where_builder
            )
        );
    }

    public function visitParenthesis(Parenthesis $parenthesis, $parameters)
    {
        return $this->visitOrExpression($parenthesis->or_expression, $parameters);
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

    private function buildAndClause(QueryBuilderParameters $parameters, OrOperand | AndOperand | null $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->acceptLogicalVisitor($this, $parameters);

        return new AndFromWhere($from_where_expression, $from_where_tail);
    }

    private function buildOrClause(QueryBuilderParameters $parameters, OrOperand | AndOperand | null $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->acceptLogicalVisitor($this, $parameters);

        return new OrFromWhere($from_where_expression, $from_where_tail);
    }

    public function visitWithParent(WithParent $condition, $parameters)
    {
        return $this->artifact_link_from_where_builder->getFromWhereForWithParent($condition, $parameters->user);
    }

    public function visitWithoutParent(WithoutParent $condition, $parameters)
    {
        return $this->artifact_link_from_where_builder->getFromWhereForWithoutParent($condition, $parameters->user);
    }
}
