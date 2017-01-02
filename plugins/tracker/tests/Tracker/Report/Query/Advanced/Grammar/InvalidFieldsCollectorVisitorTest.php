<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

use TuleapTestCase;

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class InvalidFieldsCollectorVisitorTest extends TuleapTestCase
{

    private $tracker;
    private $field;
    private $formelement_factory;
    private $collector;
    private $user;
    private $parameters;

    public function setUp()
    {
        parent::setUp();

        $this->tracker             = aTracker()->withId(101)->build();
        $this->field               = aStringField()->build();
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->user                = aUser()->build();
        $this->parameters          = new InvalidFieldsCollectorParameters($this->user, $this->tracker);

        $this->collector = new InvalidFieldsCollectorVisitor($this->formelement_factory);
    }

    public function itDoesNotThrowAnExceptionIfFieldIsUsed()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns($this->field);

        $expr = new Comparison('field', 'value');

        $this->collector->visitComparison($expr, $this->parameters);
    }

    public function itThrowsAnExceptionIfFieldIsUnknown()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(null);

        $expr = new Comparison('field', 'value');

        $invalid_fields_collection = $this->collector->collectErrorsFields($expr, $this->parameters);

        $this->assertEqual($invalid_fields_collection->getNonexistentFields(), array('field'));
        $this->assertEqual($invalid_fields_collection->getUnsupportedFields(), array());
    }

    public function itThrowsAnExceptionIfFieldIsNotText()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(aSelectBoxField()->build());

        $expr = new Comparison('field', 'value');

        $invalid_fields_collection = $this->collector->collectErrorsFields($expr, $this->parameters);

        $this->assertEqual($invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($invalid_fields_collection->getUnsupportedFields(), array('field'));
    }

    public function itDelegatesValidationToSubExpressionAndTailInAndExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $tail          = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand');
        $expression    = new AndExpression($subexpression, $tail);

        expect($subexpression)->accept($this->collector, $this->parameters)->once();
        expect($tail)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitAndExpression($expression, $this->parameters);
    }

    public function itDelegatesValidationToSubExpressionAndTailInOrExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression');
        $tail          = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand');
        $expression    = new OrExpression($subexpression, $tail);

        expect($subexpression)->accept($this->collector, $this->parameters)->once();
        expect($tail)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitOrExpression($expression, $this->parameters);
    }

    public function itDelegatesValidationToOperandAndTailInOrOperand()
    {
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression');
        $tail       = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand');
        $expression = new OrOperand($operand, $tail);

        expect($operand)->accept($this->collector, $this->parameters)->once();
        expect($tail)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitOrOperand($expression, $this->parameters);
    }

    public function itDelegatesValidationToOperandAndTailInAndOperand()
    {
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $tail       = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand');
        $expression = new AndOperand($operand, $tail);

        expect($operand)->accept($this->collector, $this->parameters)->once();
        expect($tail)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitAndOperand($expression, $this->parameters);
    }

    public function itDelegatesValidationToSubExpressionInAndExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $tail          = null;
        $expression    = new AndExpression($subexpression, $tail);

        expect($subexpression)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitAndExpression($expression, $this->parameters);
    }

    public function itDelegatesValidationToSubExpressionInOrExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression');
        $tail          = null;
        $expression    = new OrExpression($subexpression, $tail);

        expect($subexpression)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitOrExpression($expression, $this->parameters);
    }

    public function itDelegatesValidationToOperandInOrOperand()
    {
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression');
        $tail       = null;
        $expression = new OrOperand($operand, $tail);

        expect($operand)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitOrOperand($expression, $this->parameters);
    }

    public function itDelegatesValidationToOperandInAndOperand()
    {
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $tail       = null;
        $expression = new AndOperand($operand, $tail);

        expect($operand)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitAndOperand($expression, $this->parameters);
    }
}
