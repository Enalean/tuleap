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
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\ICheckMetadataForAComparison;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
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
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;

class InvalidComparisonCollectorVisitor implements Visitor
{
    /** @var InvalidSearchableCollectorVisitor */
    private $invalid_searchable_collector_visitor;

    /** @var MetadataChecker */
    private $metadata_checker;

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
        MetadataChecker $metadata_checker,
        EqualComparisonChecker $equal_comparison_checker,
        NotEqualComparisonChecker $not_equal_comparison_checker,
        GreaterThanComparisonChecker $greater_than_comparison_checker,
        GreaterThanOrEqualComparisonChecker $greater_than_or_equal_comparison_checker,
        LesserThanComparisonChecker $lesser_than_comparison_checker,
        LesserThanOrEqualComparisonChecker $lesser_than_or_equal_comparison_checker,
        BetweenComparisonChecker $between_comparison_checker,
        InComparisonChecker $in_comparison_checker,
        NotInComparisonChecker $not_in_comparison_checker,
    ) {
        $this->invalid_searchable_collector_visitor     = $invalid_searchable_collector_visitor;
        $this->metadata_checker                         = $metadata_checker;
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
        Visitable $parsed_query,
        InvalidSearchablesCollection $invalid_searchables_collection,
        array $trackers,
        PFUser $user,
    ) {
        $parsed_query->accept(
            $this,
            new InvalidComparisonCollectorParameters($invalid_searchables_collection, $trackers, $user)
        );
    }

    public function visitEqualComparison(EqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_checker,
            $this->equal_comparison_checker,
            $parameters
        );
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_checker,
            $this->not_equal_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_checker,
            $this->lesser_than_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_checker,
            $this->greater_than_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_checker,
            $this->lesser_than_or_equal_comparison_checker,
            $parameters
        );
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_checker,
            $this->greater_than_or_equal_comparison_checker,
            $parameters
        );
    }

    public function visitBetweenComparison(BetweenComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_checker,
            $this->between_comparison_checker,
            $parameters
        );
    }

    public function visitInComparison(InComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_checker,
            $this->in_comparison_checker,
            $parameters
        );
    }

    public function visitNotInComparison(NotInComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->metadata_checker,
            $this->not_in_comparison_checker,
            $parameters
        );
    }

    private function visitComparison(
        Comparison $comparison,
        ICheckMetadataForAComparison $metadata_checker,
        ComparisonChecker $comparison_checker,
        InvalidComparisonCollectorParameters $parameters,
    ) {
        $comparison->getSearchable()->acceptSearchableVisitor(
            $this->invalid_searchable_collector_visitor,
            new InvalidSearchableCollectorParameters(
                $parameters,
                $metadata_checker,
                $comparison_checker,
                $comparison
            )
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

    private function visitExpression($expression, InvalidComparisonCollectorParameters $parameters)
    {
        $expression->getExpression()->accept($this, $parameters);
        $this->visitTail($expression->getTail(), $parameters);
    }

    public function visitOrOperand(OrOperand $or_operand, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitOperand($or_operand, $parameters);
    }

    public function visitAndOperand(AndOperand $and_operand, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitOperand($and_operand, $parameters);
    }

    private function visitOperand($operand, InvalidComparisonCollectorParameters $parameters)
    {
        $operand->getOperand()->accept($this, $parameters);
        $this->visitTail($operand->getTail(), $parameters);
    }

    private function visitTail($tail, InvalidComparisonCollectorParameters $parameters)
    {
        if ($tail) {
            $tail->accept($this, $parameters);
        }
    }
}
