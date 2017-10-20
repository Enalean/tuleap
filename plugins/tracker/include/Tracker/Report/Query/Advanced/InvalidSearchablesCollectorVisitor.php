<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

use PFUser;
use Tracker;
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
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields;

class InvalidSearchablesCollectorVisitor implements Visitor
{
    /**
     * @var InvalidFields\EqualComparisonVisitor
     */
    private $equal_comparison_visitor;
    /**
     * @var InvalidFields\NotEqualComparisonVisitor
     */
    private $not_equal_comparison_visitor;
    /**
     * @var InvalidFields\LesserThanComparisonVisitor
     */
    private $lesser_than_comparison_visitor;
    /**
     * @var InvalidFields\GreaterThanComparisonVisitor
     */
    private $greater_than_comparison_visitor;
    /**
     * @var InvalidFields\LesserThanOrEqualComparisonVisitor
     */
    private $lesser_than_or_equal_comparison_visitor;
    /**
     * @var InvalidFields\GreaterThanOrEqualComparisonVisitor
     */
    private $greater_than_or_equal_comparison_visitor;
    /**
     * @var InvalidFields\BetweenComparisonVisitor
     */
    private $between_comparison_visitor;

    /**
     * @var InvalidFields\InComparisonVisitor
     */
    private $in_comparison_visitor;

    /**
     * @var InvalidFields\NotInComparisonVisitor
     */
    private $not_in_comparison_visitor;
    /**
     * @var RealInvalidSearchableCollectorVisitor
     */
    private $invalid_searchable_collector_visitor;

    public function __construct(
        InvalidFields\EqualComparisonVisitor $equal_comparison_visitor,
        InvalidFields\NotEqualComparisonVisitor $not_equal_comparison_visitor,
        InvalidFields\LesserThanComparisonVisitor $lesser_than_comparison_visitor,
        InvalidFields\GreaterThanComparisonVisitor $greater_than_comparison_visitor,
        InvalidFields\LesserThanOrEqualComparisonVisitor $lesser_than_or_equal_comparison_visitor,
        InvalidFields\GreaterThanOrEqualComparisonVisitor $greater_than_or_equal_comparison_visitor,
        InvalidFields\BetweenComparisonVisitor $between_comparison_visitor,
        InvalidFields\InComparisonVisitor $in_comparison_visitor,
        InvalidFields\NotInComparisonVisitor $not_in_comparison_visitor,
        RealInvalidSearchableCollectorVisitor $invalid_searchable_collector_visitor
    ) {
        $this->equal_comparison_visitor                 = $equal_comparison_visitor;
        $this->not_equal_comparison_visitor             = $not_equal_comparison_visitor;
        $this->lesser_than_comparison_visitor           = $lesser_than_comparison_visitor;
        $this->greater_than_comparison_visitor          = $greater_than_comparison_visitor;
        $this->lesser_than_or_equal_comparison_visitor  = $lesser_than_or_equal_comparison_visitor;
        $this->greater_than_or_equal_comparison_visitor = $greater_than_or_equal_comparison_visitor;
        $this->between_comparison_visitor               = $between_comparison_visitor;
        $this->in_comparison_visitor                    = $in_comparison_visitor;
        $this->not_in_comparison_visitor                = $not_in_comparison_visitor;
        $this->invalid_searchable_collector_visitor     = $invalid_searchable_collector_visitor;
    }

    public function collectErrors(
        Visitable $parsed_query,
        PFUser $user,
        Tracker $tracker,
        InvalidSearchablesCollection $invalid_searchables_collection
    ) {
        $parsed_query->accept($this, new InvalidSearchablesCollectorParameters($user, $tracker, $invalid_searchables_collection));
    }

    public function visitEqualComparison(EqualComparison $comparison, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->equal_comparison_visitor, $parameters);
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->not_equal_comparison_visitor, $parameters);
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->lesser_than_comparison_visitor, $parameters);
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->greater_than_comparison_visitor, $parameters);
    }

    public function visitLesserThanOrEqualComparison(LesserThanOrEqualComparison $comparison, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->lesser_than_or_equal_comparison_visitor, $parameters);
    }

    public function visitGreaterThanOrEqualComparison(GreaterThanOrEqualComparison $comparison, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->greater_than_or_equal_comparison_visitor, $parameters);
    }

    public function visitBetweenComparison(BetweenComparison $comparison, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->between_comparison_visitor, $parameters);
    }

    public function visitInComparison(InComparison $comparison, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->in_comparison_visitor, $parameters);
    }

    public function visitNotInComparison(NotInComparison $comparison, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->not_in_comparison_visitor, $parameters);
    }

    public function visitAndExpression(AndExpression $and_expression, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitExpression($and_expression, $parameters);
    }

    public function visitOrExpression(OrExpression $or_expression, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitExpression($or_expression, $parameters);
    }

    public function visitOrOperand(OrOperand $or_operand, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitOperand($or_operand, $parameters);
    }

    public function visitAndOperand(AndOperand $and_operand, InvalidSearchablesCollectorParameters $parameters)
    {
        $this->visitOperand($and_operand, $parameters);
    }

    private function visitTail($tail, InvalidSearchablesCollectorParameters $parameters)
    {
        if ($tail) {
            $tail->accept($this, $parameters);
        }
    }

    private function visitComparison(
        Comparison $comparison,
        InvalidFields\IProvideTheInvalidFieldCheckerForAComparison $checker_provider,
        InvalidSearchablesCollectorParameters $parameters
    ) {
        $comparison->getSearchable()->accept(
            $this->invalid_searchable_collector_visitor,
            new RealInvalidSearchableCollectorParameters(
                $parameters,
                $checker_provider,
                $comparison
            )
        );
    }

    private function visitExpression($expression, InvalidSearchablesCollectorParameters $parameters)
    {
        $expression->getExpression()->accept($this, $parameters);
        $this->visitTail($expression->getTail(), $parameters);
    }

    private function visitOperand($operand, InvalidSearchablesCollectorParameters $parameters)
    {
        $operand->getOperand()->accept($this, $parameters);
        $this->visitTail($operand->getTail(), $parameters);
    }
}
