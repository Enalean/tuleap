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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;

class InvalidComparisonCollectorVisitor implements Visitor
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
        Visitable $parsed_query,
        InvalidSearchablesCollection $invalid_searchables_collection,
    ) {
        $parsed_query->accept($this, new InvalidComparisonCollectorParameters($invalid_searchables_collection));
    }

    public function visitEqualComparison(EqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_equal_comparison_visitor,
            $this->metadata_equal_comparison_checker,
            $parameters
        );
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_not_equal_comparison_visitor,
            $this->metadata_not_equal_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_lesser_than_comparison_visitor,
            $this->metadata_lesser_than_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_greater_than_comparison_visitor,
            $this->metadata_greater_than_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_lesser_than_or_equal_comparison_visitor,
            $this->metadata_lesser_than_or_equal_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_greater_than_or_equal_comparison_visitor,
            $this->metadata_greater_than_comparison_checker,
            $parameters
        );
    }

    public function visitBetweenComparison(BetweenComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_between_comparison_visitor,
            $this->metadata_between_comparison_checker,
            $parameters
        );
    }

    public function visitInComparison(InComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_in_comparison_visitor,
            $this->metadata_in_comparison_checker,
            $parameters
        );
    }

    public function visitNotInComparison(NotInComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->field_not_in_comparison_visitor,
            $this->metadata_not_in_comparison_checker,
            $parameters
        );
    }

    public function visitAndExpression(AndExpression $and_expression, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitExpression($and_expression, $parameters);
    }

    public function visitOrExpression(OrExpression $or_expression, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitExpression($or_expression, $parameters);
    }

    public function visitOrOperand(OrOperand $or_operand, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitOperand($or_operand, $parameters);
    }

    public function visitAndOperand(AndOperand $and_operand, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitOperand($and_operand, $parameters);
    }

    private function visitTail($tail, InvalidComparisonCollectorParameters $parameters)
    {
        if ($tail) {
            $tail->accept($this, $parameters);
        }
    }

    private function visitComparison(
        Comparison $comparison,
        InvalidFields\IProvideTheInvalidFieldCheckerForAComparison $checker_provider,
        InvalidMetadata\ICheckMetadataForAComparison $metadata_checker,
        InvalidComparisonCollectorParameters $parameters,
    ) {
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

    private function visitExpression($expression, InvalidComparisonCollectorParameters $parameters)
    {
        $expression->getExpression()->accept($this, $parameters);
        $this->visitTail($expression->getTail(), $parameters);
    }

    private function visitOperand($operand, InvalidComparisonCollectorParameters $parameters)
    {
        $operand->getOperand()->accept($this, $parameters);
        $this->visitTail($operand->getTail(), $parameters);
    }
}
