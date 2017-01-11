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
use Tracker_FormElement_Field_Numeric;
use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;

class InvalidFieldsCollectorVisitor implements Visitor
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory)
    {
        $this->formelement_factory  = $formelement_factory;
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
        $field_name = $comparison->getField();

        $field = $this->formelement_factory->getUsedFieldByNameForUser(
            $parameters->getTracker()->getId(),
            $field_name,
            $parameters->getUser()
        );

        if (! $field) {
            $parameters->getInvalidFieldsCollection()->addNonexistentField($field_name);
        } else if (! $field instanceof Tracker_FormElement_Field_Text
            && ! $field instanceof Tracker_FormElement_Field_Numeric
        ) {
            $parameters->getInvalidFieldsCollection()->addUnsupportedField($field_name);
        }
    }

    public function visitAndExpression(AndExpression $and_expression, InvalidFieldsCollectorParameters $parameters)
    {
        $and_expression->getExpression()->accept($this, $parameters);
        $this->visitTail($and_expression->getTail(), $parameters);
    }

    public function visitOrExpression(OrExpression $or_expression, InvalidFieldsCollectorParameters $parameters)
    {
        $or_expression->getExpression()->accept($this, $parameters);
        $this->visitTail($or_expression->getTail(), $parameters);
    }

    public function visitOrOperand(OrOperand $or_operand, InvalidFieldsCollectorParameters $parameters)
    {
        $or_operand->getOperand()->accept($this, $parameters);
        $this->visitTail($or_operand->getTail(), $parameters);
    }

    public function visitAndOperand(AndOperand $and_operand, InvalidFieldsCollectorParameters $parameters)
    {
        $and_operand->getOperand()->accept($this, $parameters);
        $this->visitTail($and_operand->getTail(), $parameters);
    }

    private function visitTail($tail, InvalidFieldsCollectorParameters $parameters)
    {
        if ($tail) {
            $tail->accept($this, $parameters);
        }
    }
}
