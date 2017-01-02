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

class ValidatorTest extends TuleapTestCase
{

    private $tracker;
    private $field;
    private $formelement_factory;
    private $validator;
    private $user;

    public function setUp()
    {
        parent::setUp();

        $this->tracker             = aTracker()->withId(101)->build();
        $this->field               = aStringField()->build();
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->user                = aUser()->build();

        $this->validator = new Validator($this->formelement_factory);
    }

    public function itDoesNotThrowAnExceptionIfFieldIsUsed()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns($this->field);

        $expr = new Comparison('field', 'value');

        $this->validator->visitComparison($expr, $this->user, $this->tracker);
    }

    public function itThrowsAnExceptionIfFieldIsUnknown()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(null);

        $expr = new Comparison('field', 'value');

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\Grammar\FieldDoesNotExistException');
        $this->validator->visitComparison($expr, $this->user, $this->tracker);
    }

    public function itThrowsAnExceptionIfFieldIsNotText()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(aSelectBoxField()->build());

        $expr = new Comparison('field', 'value');

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\Grammar\FieldIsNotSupportedException');
        $this->validator->visitComparison($expr, $this->user, $this->tracker);
    }

    public function itDelegatesValidationToSubExpressionAndTailInAndExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $tail          = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand');
        $expression    = new AndExpression($subexpression, $tail);

        expect($subexpression)->accept($this->validator, $this->user, $this->tracker)->once();
        expect($tail)->accept($this->validator, $this->user, $this->tracker)->once();

        $this->validator->visitAndExpression($expression, $this->user, $this->tracker);
    }

    public function itDelegatesValidationToSubExpressionAndTailInOrExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression');
        $tail          = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand');
        $expression    = new OrExpression($subexpression, $tail);

        expect($subexpression)->accept($this->validator, $this->user, $this->tracker)->once();
        expect($tail)->accept($this->validator, $this->user, $this->tracker)->once();

        $this->validator->visitOrExpression($expression, $this->user, $this->tracker);
    }

    public function itDelegatesValidationToOperandAndTailInOrOperand()
    {
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression');
        $tail       = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand');
        $expression = new OrOperand($operand, $tail);

        expect($operand)->accept($this->validator, $this->user, $this->tracker)->once();
        expect($tail)->accept($this->validator, $this->user, $this->tracker)->once();

        $this->validator->visitOrOperand($expression, $this->user, $this->tracker);
    }

    public function itDelegatesValidationToOperandAndTailInAndOperand()
    {
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $tail       = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand');
        $expression = new AndOperand($operand, $tail);

        expect($operand)->accept($this->validator, $this->user, $this->tracker)->once();
        expect($tail)->accept($this->validator, $this->user, $this->tracker)->once();

        $this->validator->visitAndOperand($expression, $this->user, $this->tracker);
    }

    public function itDelegatesValidationToSubExpressionInAndExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $tail          = null;
        $expression    = new AndExpression($subexpression, $tail);

        expect($subexpression)->accept($this->validator, $this->user, $this->tracker)->once();

        $this->validator->visitAndExpression($expression, $this->user, $this->tracker);
    }

    public function itDelegatesValidationToSubExpressionInOrExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression');
        $tail          = null;
        $expression    = new OrExpression($subexpression, $tail);

        expect($subexpression)->accept($this->validator, $this->user, $this->tracker)->once();

        $this->validator->visitOrExpression($expression, $this->user, $this->tracker);
    }

    public function itDelegatesValidationToOperandInOrOperand()
    {
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression');
        $tail       = null;
        $expression = new OrOperand($operand, $tail);

        expect($operand)->accept($this->validator, $this->user, $this->tracker)->once();

        $this->validator->visitOrOperand($expression, $this->user, $this->tracker);
    }

    public function itDelegatesValidationToOperandInAndOperand()
    {
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $tail       = null;
        $expression = new AndOperand($operand, $tail);

        expect($operand)->accept($this->validator, $this->user, $this->tracker)->once();

        $this->validator->visitAndOperand($expression, $this->user, $this->tracker);
    }
}
