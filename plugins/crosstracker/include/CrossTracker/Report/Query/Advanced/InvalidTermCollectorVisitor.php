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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use PFUser;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Between\BetweenComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Equal\EqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\In\InComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotEqual\NotEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotIn\NotInComparisonChecker;
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
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink\ArtifactLinkTypeChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink\InvalidArtifactLinkTypeException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;

/**
 * @template-implements LogicalVisitor<InvalidComparisonCollectorParameters, void>
 * @template-implements TermVisitor<InvalidComparisonCollectorParameters, void>
 * @template-implements LinkConditionVisitor<InvalidComparisonCollectorParameters, void>
 */
final class InvalidTermCollectorVisitor implements LogicalVisitor, TermVisitor, LinkConditionVisitor
{
    /** @var InvalidSearchableCollectorVisitor */
    private $invalid_searchable_collector_visitor;

    /** @var EqualComparisonChecker */
    private $equal_comparison_checker;

    /** @var NotEqualComparisonChecker */
    private $not_equal_comparison_checker;

    /** @var GreaterThanComparisonChecker */
    private $greater_than_comparison_checker;

    /** @var GreaterThanOrEqualComparisonChecker */
    private $greater_than_or_equal_comparison_checker;

    /** @var LesserThanComparisonChecker */
    private $lesser_than_comparison_checker;

    /** @var LesserThanOrEqualComparisonChecker */
    private $lesser_than_or_equal_comparison_checker;

    /** @var BetweenComparisonChecker */
    private $between_comparison_checker;

    /** @var InComparisonChecker */
    private $in_comparison_checker;

    /** @var NotInComparisonChecker */
    private $not_in_comparison_checker;

    public function __construct(
        InvalidSearchableCollectorVisitor $invalid_searchable_collector_visitor,
        EqualComparisonChecker $equal_comparison_checker,
        NotEqualComparisonChecker $not_equal_comparison_checker,
        GreaterThanComparisonChecker $greater_than_comparison_checker,
        GreaterThanOrEqualComparisonChecker $greater_than_or_equal_comparison_checker,
        LesserThanComparisonChecker $lesser_than_comparison_checker,
        LesserThanOrEqualComparisonChecker $lesser_than_or_equal_comparison_checker,
        BetweenComparisonChecker $between_comparison_checker,
        InComparisonChecker $in_comparison_checker,
        NotInComparisonChecker $not_in_comparison_checker,
        private readonly ArtifactLinkTypeChecker $artifact_link_type_checker,
    ) {
        $this->invalid_searchable_collector_visitor     = $invalid_searchable_collector_visitor;
        $this->equal_comparison_checker                 = $equal_comparison_checker;
        $this->not_equal_comparison_checker             = $not_equal_comparison_checker;
        $this->greater_than_comparison_checker          = $greater_than_comparison_checker;
        $this->greater_than_or_equal_comparison_checker = $greater_than_or_equal_comparison_checker;
        $this->lesser_than_comparison_checker           = $lesser_than_comparison_checker;
        $this->lesser_than_or_equal_comparison_checker  = $lesser_than_or_equal_comparison_checker;
        $this->between_comparison_checker               = $between_comparison_checker;
        $this->in_comparison_checker                    = $in_comparison_checker;
        $this->not_in_comparison_checker                = $not_in_comparison_checker;
    }

    /**
     * @param Tracker[] $trackers
     */
    public function collectErrors(
        Logical $parsed_query,
        InvalidSearchablesCollection $invalid_searchables_collection,
        array $trackers,
        PFUser $user,
    ): void {
        $parsed_query->acceptLogicalVisitor(
            $this,
            new InvalidComparisonCollectorParameters($invalid_searchables_collection, $trackers, $user)
        );
    }

    public function visitEqualComparison(EqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->equal_comparison_checker,
            $parameters
        );
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->not_equal_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->lesser_than_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->greater_than_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->lesser_than_or_equal_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->greater_than_or_equal_comparison_checker,
            $parameters
        );
    }

    public function visitBetweenComparison(BetweenComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->between_comparison_checker,
            $parameters
        );
    }

    public function visitInComparison(InComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->in_comparison_checker,
            $parameters
        );
    }

    public function visitNotInComparison(NotInComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->not_in_comparison_checker,
            $parameters
        );
    }

    private function visitComparison(
        Comparison $comparison,
        ComparisonChecker $comparison_checker,
        InvalidComparisonCollectorParameters $parameters,
    ): void {
        $comparison->getSearchable()->acceptSearchableVisitor(
            $this->invalid_searchable_collector_visitor,
            new InvalidSearchableCollectorParameters(
                $parameters,
                $comparison_checker,
                $comparison
            )
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
