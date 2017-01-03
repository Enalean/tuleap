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

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;

class InvalidFieldsCollectorVisitor implements Visitor
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var array
     */
    private $fields_not_exist;
    /**
     * @var array
     */
    private $fields_not_supported;

    public function __construct(Tracker_FormElementFactory $formelement_factory)
    {
        $this->fields_not_exist     = array();
        $this->fields_not_supported = array();
        $this->formelement_factory  = $formelement_factory;
    }

    public function collectErrorsFields($parsed_query, InvalidFieldsCollectorParameters $parameters)
    {
        $this->fields_not_exist     = array();
        $this->fields_not_supported = array();

        $parsed_query->accept($this, $parameters);

        return new InvalidFieldsCollection($this->fields_not_exist, $this->fields_not_supported);
    }

    public function visitComparison(Comparison $comparison, InvalidFieldsCollectorParameters $parameters)
    {
        $field = $this->formelement_factory->getUsedFieldByNameForUser(
            $parameters->getTracker()->getId(),
            $comparison->getField(),
            $parameters->getUser()
        );

        if (! $field) {
            $this->fields_not_exist[] = $comparison->getField();
        } else if (! $field instanceof Tracker_FormElement_Field_Text) {
            $this->fields_not_supported[] = $comparison->getField();
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
