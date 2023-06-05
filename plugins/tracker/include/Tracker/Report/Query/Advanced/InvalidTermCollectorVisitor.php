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

use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
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

/**
 * @template-implements LogicalVisitor<InvalidComparisonCollectorParameters, void>
 * @template-implements TermVisitor<InvalidComparisonCollectorParameters, void>
 */
final class InvalidTermCollectorVisitor implements LogicalVisitor, TermVisitor
{
    /**
     * @var InvalidFields\EqualComparisonVisitor
     */
    private $field_equal_comparison_visitor;
    /**
     * @var InvalidFields\NotEqualComparisonVisitor
     */
    private $field_not_equal_comparison_visitor;
    /**
     * @var InvalidFields\LesserThanComparisonVisitor
     */
    private $field_lesser_than_comparison_visitor;
    /**
     * @var InvalidFields\GreaterThanComparisonVisitor
     */
    private $field_greater_than_comparison_visitor;
    /**
     * @var InvalidFields\LesserThanOrEqualComparisonVisitor
     */
    private $field_lesser_than_or_equal_comparison_visitor;
    /**
     * @var InvalidFields\GreaterThanOrEqualComparisonVisitor
     */
    private $field_greater_than_or_equal_comparison_visitor;
    /**
     * @var InvalidFields\BetweenComparisonVisitor
     */
    private $field_between_comparison_visitor;

    /**
     * @var InvalidFields\InComparisonVisitor
     */
    private $field_in_comparison_visitor;

    /**
     * @var InvalidFields\NotInComparisonVisitor
     */
    private $field_not_in_comparison_visitor;
    /**
     * @var InvalidSearchableCollectorVisitor
     */
    private $invalid_searchable_collector_visitor;
    /**
     * @var InvalidMetadata\EqualComparisonChecker
     */
    private $metadata_equal_comparison_checker;
    /**
     * @var InvalidMetadata\NotEqualComparisonChecker
     */
    private $metadata_not_equal_comparison_checker;
    /**
     * @var InvalidMetadata\LesserThanComparisonChecker
     */
    private $metadata_lesser_than_comparison_checker;
    /**
     * @var InvalidMetadata\GreaterThanComparisonChecker
     */
    private $metadata_greater_than_comparison_checker;
    /**
     * @var InvalidMetadata\LesserThanOrEqualComparisonChecker
     */
    private $metadata_lesser_than_or_equal_comparison_checker;
    /**
     * @var InvalidMetadata\BetweenComparisonChecker
     */
    private $metadata_between_comparison_checker;
    /**
     * @var InvalidMetadata\InComparisonChecker
     */
    private $metadata_in_comparison_checker;
    /**
     * @var InvalidMetadata\NotInComparisonChecker
     */
    private $metadata_not_in_comparison_checker;

    public function __construct(
        InvalidFields\EqualComparisonVisitor $field_equal_comparison_visitor,
        InvalidFields\NotEqualComparisonVisitor $field_not_equal_comparison_visitor,
        InvalidFields\LesserThanComparisonVisitor $field_lesser_than_comparison_visitor,
        InvalidFields\GreaterThanComparisonVisitor $field_greater_than_comparison_visitor,
        InvalidFields\LesserThanOrEqualComparisonVisitor $field_lesser_than_or_equal_comparison_visitor,
        InvalidFields\GreaterThanOrEqualComparisonVisitor $field_greater_than_or_equal_comparison_visitor,
        InvalidFields\BetweenComparisonVisitor $field_between_comparison_visitor,
        InvalidFields\InComparisonVisitor $field_in_comparison_visitor,
        InvalidFields\NotInComparisonVisitor $field_not_in_comparison_visitor,
        InvalidMetadata\EqualComparisonChecker $metadata_equal_comparison_checker,
        InvalidMetadata\NotEqualComparisonChecker $metadata_not_equal_comparison_checker,
        InvalidMetadata\LesserThanComparisonChecker $metadata_lesser_than_comparison_checker,
        InvalidMetadata\GreaterThanComparisonChecker $metadata_greater_than_comparison_checker,
        InvalidMetadata\LesserThanOrEqualComparisonChecker $metadata_lesser_than_or_equal_comparison_checker,
        InvalidMetadata\BetweenComparisonChecker $metadata_between_comparison_checker,
        InvalidMetadata\InComparisonChecker $metadata_in_comparison_checker,
        InvalidMetadata\NotInComparisonChecker $metadata_not_in_comparison_checker,
        InvalidSearchableCollectorVisitor $invalid_searchable_collector_visitor,
    ) {
        $this->field_equal_comparison_visitor                   = $field_equal_comparison_visitor;
        $this->field_not_equal_comparison_visitor               = $field_not_equal_comparison_visitor;
        $this->field_lesser_than_comparison_visitor             = $field_lesser_than_comparison_visitor;
        $this->field_greater_than_comparison_visitor            = $field_greater_than_comparison_visitor;
        $this->field_lesser_than_or_equal_comparison_visitor    = $field_lesser_than_or_equal_comparison_visitor;
        $this->field_greater_than_or_equal_comparison_visitor   = $field_greater_than_or_equal_comparison_visitor;
        $this->field_between_comparison_visitor                 = $field_between_comparison_visitor;
        $this->field_in_comparison_visitor                      = $field_in_comparison_visitor;
        $this->field_not_in_comparison_visitor                  = $field_not_in_comparison_visitor;
        $this->invalid_searchable_collector_visitor             = $invalid_searchable_collector_visitor;
        $this->metadata_equal_comparison_checker                = $metadata_equal_comparison_checker;
        $this->metadata_not_equal_comparison_checker            = $metadata_not_equal_comparison_checker;
        $this->metadata_lesser_than_comparison_checker          = $metadata_lesser_than_comparison_checker;
        $this->metadata_greater_than_comparison_checker         = $metadata_greater_than_comparison_checker;
        $this->metadata_lesser_than_or_equal_comparison_checker = $metadata_lesser_than_or_equal_comparison_checker;
        $this->metadata_between_comparison_checker              = $metadata_between_comparison_checker;
        $this->metadata_in_comparison_checker                   = $metadata_in_comparison_checker;
        $this->metadata_not_in_comparison_checker               = $metadata_not_in_comparison_checker;
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
            $this->field_equal_comparison_visitor,
            $this->metadata_equal_comparison_checker,
            $parameters
        );
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_not_equal_comparison_visitor,
            $this->metadata_not_equal_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_lesser_than_comparison_visitor,
            $this->metadata_lesser_than_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_greater_than_comparison_visitor,
            $this->metadata_greater_than_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_lesser_than_or_equal_comparison_visitor,
            $this->metadata_lesser_than_or_equal_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_greater_than_or_equal_comparison_visitor,
            $this->metadata_greater_than_comparison_checker,
            $parameters
        );
    }

    public function visitBetweenComparison(BetweenComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_between_comparison_visitor,
            $this->metadata_between_comparison_checker,
            $parameters
        );
    }

    public function visitInComparison(InComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_in_comparison_visitor,
            $this->metadata_in_comparison_checker,
            $parameters
        );
    }

    public function visitNotInComparison(NotInComparison $comparison, $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_not_in_comparison_visitor,
            $this->metadata_not_in_comparison_checker,
            $parameters
        );
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
        InvalidFields\IProvideTheInvalidFieldCheckerForAComparison $checker_provider,
        InvalidMetadata\ICheckMetadataForAComparison $metadata_checker,
        InvalidComparisonCollectorParameters $parameters,
    ): void {
        $comparison->getSearchable()->acceptSearchableVisitor(
            $this->invalid_searchable_collector_visitor,
            new InvalidSearchableCollectorParameters(
                $parameters,
                $checker_provider,
                $metadata_checker,
                $comparison
            )
        );
    }

    public function visitWithParent(WithParent $condition, $parameters)
    {
        // Always valid
    }

    public function visitWithoutParent(WithoutParent $condition, $parameters)
    {
        // Always valid
    }
}
