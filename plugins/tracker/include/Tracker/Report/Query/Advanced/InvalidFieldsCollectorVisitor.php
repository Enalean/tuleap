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
use Tracker_FormElementFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ICheckThatFieldIsAllowedForComparison;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\LesserThanComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NotEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\GreaterThanComparisonVisitor;

class InvalidFieldsCollectorVisitor implements Visitor
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var EqualComparisonVisitor
     */
    private $equal_comparison_visitor;
    /**
     * @var NotEqualComparisonVisitor
     */
    private $not_equal_comparison_visitor;
    /**
     * @var LesserThanComparisonVisitor
     */
    private $lesser_than_comparison_visitor;
    /**
     * @var GreaterThanComparisonVisitor
     */
    private $greater_than_comparison_visitor;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        EqualComparisonVisitor $equal_comparison_visitor,
        NotEqualComparisonVisitor $not_equal_comparison_visitor,
        LesserThanComparisonVisitor $lesser_than_comparison_visitor,
        GreaterThanComparisonVisitor $greater_than_comparison_visitor
    ) {
        $this->formelement_factory             = $formelement_factory;
        $this->equal_comparison_visitor        = $equal_comparison_visitor;
        $this->not_equal_comparison_visitor    = $not_equal_comparison_visitor;
        $this->lesser_than_comparison_visitor  = $lesser_than_comparison_visitor;
        $this->greater_than_comparison_visitor = $greater_than_comparison_visitor;
    }

    public function collectErrorsFields(
        Visitable $parsed_query,
        PFUser $user,
        Tracker $tracker,
        InvalidFieldsCollection $invalid_fields_collection
    ) {
        $parsed_query->accept($this, new InvalidFieldsCollectorParameters($user, $tracker, $invalid_fields_collection));
    }

    public function visitEqualComparison(EqualComparison $comparison, InvalidFieldsCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->equal_comparison_visitor, $parameters);
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, InvalidFieldsCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->not_equal_comparison_visitor, $parameters);
    }

    public function visitLesserThanComparison(LesserThanComparison $comparison, InvalidFieldsCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->lesser_than_comparison_visitor, $parameters);
    }

    public function visitGreaterThanComparison(GreaterThanComparison $comparison, InvalidFieldsCollectorParameters $parameters)
    {
        $this->visitComparison($comparison, $this->greater_than_comparison_visitor, $parameters);
    }

    public function visitAndExpression(AndExpression $and_expression, InvalidFieldsCollectorParameters $parameters)
    {
        $this->visitExpression($and_expression, $parameters);
    }

    public function visitOrExpression(OrExpression $or_expression, InvalidFieldsCollectorParameters $parameters)
    {
        $this->visitExpression($or_expression, $parameters);
    }

    public function visitOrOperand(OrOperand $or_operand, InvalidFieldsCollectorParameters $parameters)
    {
        $this->visitOperand($or_operand, $parameters);
    }

    public function visitAndOperand(AndOperand $and_operand, InvalidFieldsCollectorParameters $parameters)
    {
        $this->visitOperand($and_operand, $parameters);
    }

    private function visitTail($tail, InvalidFieldsCollectorParameters $parameters)
    {
        if ($tail) {
            $tail->accept($this, $parameters);
        }
    }

    private function visitComparison(
        Comparison $comparison,
        ICheckThatFieldIsAllowedForComparison $checker,
        InvalidFieldsCollectorParameters $parameters
    ) {
        $field_name = $comparison->getField();

        $field = $this->formelement_factory->getUsedFieldByNameForUser(
            $parameters->getTracker()->getId(),
            $field_name,
            $parameters->getUser()
        );

        if (! $field) {
            $parameters->getInvalidFieldsCollection()->addNonexistentField($field_name);
        } else {
            try {
                $checker->checkThatFieldIsAllowed($field);
            } catch (FieldIsNotSupportedForComparisonException $exception) {
                $parameters->getInvalidFieldsCollection()->addUnsupportedField($field_name);
            }
        }
    }

    private function visitExpression($expression, InvalidFieldsCollectorParameters $parameters)
    {
        $expression->getExpression()->accept($this, $parameters);
        $this->visitTail($expression->getTail(), $parameters);
    }

    private function visitOperand($operand, InvalidFieldsCollectorParameters $parameters)
    {
        $operand->getOperand()->accept($this, $parameters);
        $this->visitTail($operand->getTail(), $parameters);
    }
}
