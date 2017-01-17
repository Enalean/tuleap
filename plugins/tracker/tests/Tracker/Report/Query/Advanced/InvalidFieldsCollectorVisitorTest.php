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

namespace Tuleap\Tracker\Report\Query\Advanced;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValue;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\GreaterThanOrEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\LesserThanComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\LesserThanOrEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NotEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\GreaterThanComparisonVisitor;
use TuleapTestCase;

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class InvalidFieldsCollectorVisitorTest extends TuleapTestCase
{

    private $tracker;
    private $field_text;
    private $int_field;
    private $formelement_factory;
    private $collector;
    private $user;
    private $parameters;
    private $invalid_fields_collection;

    public function setUp()
    {
        parent::setUp();

        $this->tracker             = aTracker()->withId(101)->build();
        $this->field_text          = aTextField()->withName('field')->withId(101)->build();
        $this->int_field           = anIntegerField()->withName('int')->withId(102)->build();
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->user                = aUser()->build();

        $this->invalid_fields_collection = new InvalidFieldsCollection();
        $this->parameters                = new InvalidFieldsCollectorParameters(
            $this->user,
            $this->tracker,
            $this->invalid_fields_collection
        );

        $this->collector = new InvalidFieldsCollectorVisitor(
            $this->formelement_factory,
            new EqualComparisonVisitor(),
            new NotEqualComparisonVisitor(),
            new LesserThanComparisonVisitor(),
            new GreaterThanComparisonVisitor(),
            new LesserThanOrEqualComparisonVisitor(),
            new GreaterThanOrEqualComparisonVisitor()
        );
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns($this->field_text);

        $expr = new EqualComparison('field', new SimpleValue('value'));

        $this->collector->visitEqualComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForNotEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns($this->field_text);

        $expr = new NotEqualComparison('field', new SimpleValue('value'));

        $this->collector->visitNotEqualComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "int", $this->user)->returns($this->int_field);

        $expr = new LesserThanComparison('int', new SimpleValue(20));

        $this->collector->visitLesserThanComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "int", $this->user)->returns($this->int_field);

        $expr = new GreaterThanComparison('int', new SimpleValue(20));

        $this->collector->visitGreaterThanComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanOrEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "int", $this->user)->returns($this->int_field);

        $expr = new LesserThanOrEqualComparison('int', new SimpleValue(20));

        $this->collector->visitLesserThanOrEqualComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanOrEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "int", $this->user)->returns($this->int_field);

        $expr = new GreaterThanOrEqualComparison('int', new SimpleValue(20));

        $this->collector->visitGreaterThanOrEqualComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getUnsupportedFields(), array());
    }

    public function itCollectsNonExistentFieldsIfFieldIsUnknown()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(null);

        $expr = new EqualComparison('field', new SimpleValue('value'));

        $this->collector->collectErrorsFields($expr, $this->user, $this->tracker, $this->invalid_fields_collection);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array('field'));
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array());
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotText()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(aSelectBoxField()->build());

        $expr = new EqualComparison('field', new SimpleValue('value'));

        $this->collector->collectErrorsFields($expr, $this->user, $this->tracker, $this->invalid_fields_collection);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array('field'));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumeric()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(aSelectBoxField()->build());

        $expr = new EqualComparison('field', new SimpleValue(20));

        $this->collector->collectErrorsFields($expr, $this->user, $this->tracker, $this->invalid_fields_collection);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array('field'));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(aStringField()->build());

        $expr = new LesserThanComparison('field', new SimpleValue('value'));

        $this->collector->collectErrorsFields($expr, $this->user, $this->tracker, $this->invalid_fields_collection);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array('field'));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(aStringField()->build());

        $expr = new GreaterThanComparison('field', new SimpleValue('value'));

        $this->collector->collectErrorsFields($expr, $this->user, $this->tracker, $this->invalid_fields_collection);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getUnsupportedFields(), array('field'));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanOrEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(aStringField()->build());

        $expr = new LesserThanOrEqualComparison('field', new SimpleValue('value'));

        $this->collector->collectErrorsFields($expr, $this->user, $this->tracker, $this->invalid_fields_collection);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array('field'));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanOrEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(aStringField()->build());

        $expr = new GreaterThanOrEqualComparison('field', new SimpleValue('value'));

        $this->collector->collectErrorsFields($expr, $this->user, $this->tracker, $this->invalid_fields_collection);

        $this->assertEqual($this->invalid_fields_collection->getNonexistentFields(), array());
        $this->assertEqual($this->invalid_fields_collection->getFieldsNotSupportingOperator(), array('field'));
    }

    public function itDelegatesValidationToSubExpressionAndTailInAndExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison');
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
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison');
        $tail       = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand');
        $expression = new AndOperand($operand, $tail);

        expect($operand)->accept($this->collector, $this->parameters)->once();
        expect($tail)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitAndOperand($expression, $this->parameters);
    }

    public function itDelegatesValidationToSubExpressionInAndExpression()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison');
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
        $operand    = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison');
        $tail       = null;
        $expression = new AndOperand($operand, $tail);

        expect($operand)->accept($this->collector, $this->parameters)->once();

        $this->collector->visitAndOperand($expression, $this->parameters);
    }
}
