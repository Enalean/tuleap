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

use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\ComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\ICheckSemanticFieldForAComparison;
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
use Tuleap\Tracker\Report\Query\Advanced\ICollectErrorsForInvalidComparisons;
use Tuleap\Tracker\Report\Query\Advanced\InvalidComparisonCollectorParameters;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;

class InvalidComparisonCollectorVisitor implements Visitor, ICollectErrorsForInvalidComparisons
{
    /** @var InvalidSearchableCollectorVisitor */
    private $invalid_searchable_collector_visitor;

    /** @var ComparisonChecker */
    private $semantic_comparison_checker;

    public function __construct(
        InvalidSearchableCollectorVisitor $invalid_searchable_collector_visitor,
        ComparisonChecker $semantic_comparison_checker
    ) {
        $this->invalid_searchable_collector_visitor = $invalid_searchable_collector_visitor;
        $this->semantic_comparison_checker          = $semantic_comparison_checker;
    }

    public function collectErrors(
        Visitable $parsed_query,
        InvalidSearchablesCollection $invalid_searchables_collection
    ) {
        $parsed_query->accept($this, new InvalidComparisonCollectorParameters($invalid_searchables_collection));
    }

    public function visitEqualComparison(EqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->semantic_comparison_checker,
            $parameters
        );
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->visitComparison(
            $comparison,
            $this->semantic_comparison_checker,
            $parameters
        );
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->addUnsupportedComparisonError($parameters, "<");
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->addUnsupportedComparisonError($parameters, ">");
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->addUnsupportedComparisonError($parameters, "<=");
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->addUnsupportedComparisonError($parameters, ">=");
    }

    public function visitBetweenComparison(BetweenComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->addUnsupportedComparisonError($parameters, "BETWEEN");
    }

    public function visitInComparison(InComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->addUnsupportedComparisonError($parameters, "IN");
    }

    public function visitNotInComparison(NotInComparison $comparison, InvalidComparisonCollectorParameters $parameters)
    {
        $this->addUnsupportedComparisonError($parameters, "NOT IN");
    }

    private function visitComparison(
        Comparison $comparison,
        ICheckSemanticFieldForAComparison $semantic_checker,
        InvalidComparisonCollectorParameters $parameters
    ) {
        $comparison->getSearchable()->accept(
            $this->invalid_searchable_collector_visitor,
            new InvalidSearchableCollectorParameters(
                $parameters,
                $semantic_checker,
                $comparison
            )
        );
    }

    private function addUnsupportedComparisonError(InvalidComparisonCollectorParameters $parameters, $comparison_name)
    {
        $parameters->getInvalidSearchablesCollection()->addInvalidSearchableError(
            sprintf(
                dgettext(
                    "tuleap-crosstracker",
                    "The %s comparison is not supported for cross-tracker search. Please refer to the documentation for the allowed comparisons."
                ),
                $comparison_name
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
