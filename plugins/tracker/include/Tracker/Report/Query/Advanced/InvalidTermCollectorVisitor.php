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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LinkArtifactCondition;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LinkConditionVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LinkTrackerEqualCondition;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LinkTrackerNotEqualCondition;
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithForwardLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutForwardLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutReverseLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithReverseLink;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink\InvalidArtifactLinkTypeException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FlatInvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FlatFloatFieldChecker;

/**
 * @template-implements LogicalVisitor<InvalidComparisonCollectorParameters, void>
 * @template-implements TermVisitor<InvalidComparisonCollectorParameters, void>
 * @template-implements LinkConditionVisitor<InvalidComparisonCollectorParameters, void>
 */
final readonly class InvalidTermCollectorVisitor implements LogicalVisitor, TermVisitor, LinkConditionVisitor
{
    public function __construct(
        private InvalidFields\EqualComparisonVisitor $field_equal_comparison_visitor,
        private InvalidFields\NotEqualComparisonVisitor $field_not_equal_comparison_visitor,
        private InvalidFields\LesserThanComparisonVisitor $field_lesser_than_comparison_visitor,
        private InvalidFields\GreaterThanComparisonVisitor $field_greater_than_comparison_visitor,
        private InvalidFields\LesserThanOrEqualComparisonVisitor $field_lesser_than_or_equal_comparison_visitor,
        private InvalidFields\GreaterThanOrEqualComparisonVisitor $field_greater_than_or_equal_comparison_visitor,
        private InvalidFields\BetweenComparisonVisitor $field_between_comparison_visitor,
        private InvalidFields\InComparisonVisitor $field_in_comparison_visitor,
        private InvalidFields\NotInComparisonVisitor $field_not_in_comparison_visitor,
        private InvalidFields\ArtifactLink\ArtifactLinkTypeChecker $artifact_link_type_checker,
        private InvalidMetadata\EqualComparisonChecker $metadata_equal_comparison_checker,
        private InvalidMetadata\NotEqualComparisonChecker $metadata_not_equal_comparison_checker,
        private InvalidMetadata\LesserThanComparisonChecker $metadata_lesser_than_comparison_checker,
        private InvalidMetadata\GreaterThanComparisonChecker $metadata_greater_than_comparison_checker,
        private InvalidMetadata\LesserThanOrEqualComparisonChecker $metadata_lesser_than_or_equal_comparison_checker,
        private InvalidMetadata\BetweenComparisonChecker $metadata_between_comparison_checker,
        private InvalidMetadata\InComparisonChecker $metadata_in_comparison_checker,
        private InvalidMetadata\NotInComparisonChecker $metadata_not_in_comparison_checker,
        private InvalidSearchableCollectorVisitor $invalid_searchable_collector_visitor,
    ) {
    }

    public function collectErrors(
        Logical $parsed_query,
        InvalidSearchablesCollection $invalid_searchables_collection,
    ): void {
        $parsed_query->acceptLogicalVisitor($this, new InvalidComparisonCollectorParameters($invalid_searchables_collection));
    }

    public function visitEqualComparison(EqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_equal_comparison_checker,
            $parameters
        );
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_not_equal_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_lesser_than_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_greater_than_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_lesser_than_or_equal_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_greater_than_comparison_checker,
            $parameters
        );
    }

    public function visitBetweenComparison(BetweenComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_between_comparison_checker,
            $parameters
        );
    }

    public function visitInComparison(InComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_in_comparison_checker,
            $parameters
        );
    }

    public function visitNotInComparison(NotInComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_not_in_comparison_checker,
            $parameters
        );
    }

    public function visitParenthesis(Parenthesis $parenthesis, $parameters)
    {
        $this->visitOrExpression($parenthesis->or_expression, $parameters);
    }

    public function visitAndExpression(AndExpression $and_expression, $parameters)
    {
        $and_expression->getExpression()->acceptTermVisitor($this, $parameters);
        $this->visitTail($and_expression->getTail(), $parameters);
    }

    public function visitOrExpression(OrExpression $or_expression, $parameters)
    {
        $or_expression->getExpression()->acceptLogicalVisitor($this, $parameters);
        $this->visitTail($or_expression->getTail(), $parameters);
    }

    public function visitOrOperand(OrOperand $or_operand, $parameters)
    {
        $or_operand->getOperand()->acceptLogicalVisitor($this, $parameters);
        $this->visitTail($or_operand->getTail(), $parameters);
    }

    public function visitAndOperand(AndOperand $and_operand, $parameters)
    {
        $and_operand->getOperand()->acceptTermVisitor($this, $parameters);
        $this->visitTail($and_operand->getTail(), $parameters);
    }

    private function visitTail(
        OrOperand | AndOperand | null $tail,
        InvalidComparisonCollectorParameters $parameters,
    ): void {
        if ($tail) {
            $tail->acceptLogicalVisitor($this, $parameters);
        }
    }

    private function visitComparison(
        Comparison $comparison,
        InvalidMetadata\ICheckMetadataForAComparison $metadata_checker,
        InvalidComparisonCollectorParameters $parameters,
    ): void {
        $comparison->getSearchable()->acceptSearchableVisitor(
            $this->invalid_searchable_collector_visitor,
            new InvalidSearchableCollectorParameters(
                $parameters,
                new FlatInvalidFieldChecker(
                    $comparison,
                    new FlatFloatFieldChecker(),
                    $this->field_equal_comparison_visitor,
                    $this->field_not_equal_comparison_visitor,
                    $this->field_lesser_than_comparison_visitor,
                    $this->field_lesser_than_or_equal_comparison_visitor,
                    $this->field_greater_than_comparison_visitor,
                    $this->field_greater_than_or_equal_comparison_visitor,
                    $this->field_between_comparison_visitor,
                    $this->field_in_comparison_visitor,
                    $this->field_not_in_comparison_visitor
                ),
                $metadata_checker,
                $comparison
            )
        );
    }

    public function visitWithReverseLink(WithReverseLink $condition, $parameters)
    {
        $this->visitRelationshipCondition($condition, $parameters);
    }

    public function visitWithoutReverseLink(WithoutReverseLink $term, $parameters)
    {
        $this->visitRelationshipCondition($term, $parameters);
        if ($term->condition) {
            $term->condition->accept($this, $parameters);
        }
    }

    public function visitWithForwardLink(WithForwardLink $condition, $parameters)
    {
        $this->visitRelationshipCondition($condition, $parameters);
    }

    public function visitWithoutForwardLink(WithoutForwardLink $term, $parameters)
    {
        $this->visitRelationshipCondition($term, $parameters);
        if ($term->condition) {
            $term->condition->accept($this, $parameters);
        }
    }

    private function visitRelationshipCondition(
        WithReverseLink | WithoutReverseLink | WithForwardLink | WithoutForwardLink $condition,
        InvalidComparisonCollectorParameters $parameters,
    ): void {
        try {
            $this->artifact_link_type_checker->checkArtifactLinkTypeIsValid($condition);
        } catch (InvalidArtifactLinkTypeException $exception) {
            $parameters->getInvalidSearchablesCollection()->addInvalidSearchableError($exception->getMessage());
        }
    }

    public function visitLinkArtifactCondition(LinkArtifactCondition $condition, $parameters)
    {
        // It's always ok
    }

    public function visitLinkTrackerEqualCondition(LinkTrackerEqualCondition $condition, $parameters)
    {
        // It's always ok
    }

    public function visitLinkTrackerNotEqualCondition(LinkTrackerNotEqualCondition $condition, $parameters)
    {
        $parameters->getInvalidSearchablesCollection()->addInvalidSearchableError(
            sprintf(
                dgettext('tuleap-tracker', 'Double negative like `%s` or `%s` is not supported. Please use simpler form like `%s` or `%s`'),
                'IS NOT LINKED ... TRACKER != ...',
                'WITHOUT ... TRACKER != ...',
                'IS LINKED ... TRACKER = ...',
                'WITH ... TRACKER = ...',
            )
        );
    }
}
