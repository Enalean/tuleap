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

use Tracker_FormElementFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\BetweenComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\GreaterThanComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\GreaterThanOrEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\LesserThanComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\LesserThanOrEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NotEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NotInComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\BetweenComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\EqualComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\GreaterThanComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\GreaterThanOrEqualComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\InComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\LesserThanComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\LesserThanOrEqualComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\NotEqualComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\NotInComparisonChecker;
use TuleapTestCase;

require_once __DIR__.'/../../../../bootstrap.php';

class InvalidSearchablesCollectorVisitorTest extends TuleapTestCase
{

    private $tracker;
    private $field_text;
    private $int_field;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;
    /** @var InvalidComparisonCollectorVisitor */
    private $collector;
    private $user;
    private $parameters;
    /** @var InvalidSearchablesCollection */
    private $invalid_searchables_collection;

    public function setUp()
    {
        parent::setUp();

        $this->tracker             = aTracker()->withId(101)->build();
        $this->field_text          = aTextField()->withName('field')->withId(101)->build();
        $this->int_field           = anIntegerField()->withName('int')->withId(102)->build();
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->user                = aUser()->build();

        $this->invalid_searchables_collection = new InvalidSearchablesCollection();
        $this->parameters                     = new InvalidComparisonCollectorParameters(
            $this->invalid_searchables_collection
        );

        $this->collector = new InvalidComparisonCollectorVisitor(
            new EqualComparisonVisitor(),
            new NotEqualComparisonVisitor(),
            new LesserThanComparisonVisitor(),
            new GreaterThanComparisonVisitor(),
            new LesserThanOrEqualComparisonVisitor(),
            new GreaterThanOrEqualComparisonVisitor(),
            new BetweenComparisonVisitor(),
            new InComparisonVisitor(),
            new NotInComparisonVisitor(),
            new EqualComparisonChecker(),
            new NotEqualComparisonChecker(),
            new LesserThanComparisonChecker(),
            new GreaterThanComparisonChecker(),
            new LesserThanOrEqualComparisonChecker(),
            new GreaterThanOrEqualComparisonChecker(),
            new BetweenComparisonChecker(),
            new InComparisonChecker(),
            new NotInComparisonChecker(),
            new InvalidSearchableCollectorVisitor($this->formelement_factory, $this->tracker, $this->user)
        );
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldByNameForUser(101, "field", $this->user)->returns($this->field_text);

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->visitEqualComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfDateFieldIsUsedForEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldByNameForUser(101, "field", $this->user)->returns(aMockDateWithoutTimeField()->build());

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('2017-01-17'));

        $this->collector->visitEqualComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForNotEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldByNameForUser(101, "field", $this->user)->returns($this->field_text);

        $expr = new NotEqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->visitNotEqualComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanComparison()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldByNameForUser(101, "int", $this->user)->returns($this->int_field);

        $expr = new LesserThanComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->collector->visitLesserThanComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanComparison()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldByNameForUser(101, "int", $this->user)->returns($this->int_field);

        $expr = new GreaterThanComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->collector->visitGreaterThanComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanOrEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldByNameForUser(101, "int", $this->user)->returns($this->int_field);

        $expr = new LesserThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->collector->visitLesserThanOrEqualComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanOrEqualComparison()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldByNameForUser(101, "int", $this->user)->returns($this->int_field);

        $expr = new GreaterThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->collector->visitGreaterThanOrEqualComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itDoesNotCollectInvalidFieldsIfFieldIsUsedForBetweenComparison()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldByNameForUser(101, "int", $this->user)->returns($this->int_field);

        $expr = new BetweenComparison(
            new Field('int'),
            new BetweenValueWrapper(
                new SimpleValueWrapper(20),
                new SimpleValueWrapper(30)
            )
        );

        $this->collector->visitBetweenComparison($expr, $this->parameters);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itCollectsNonExistentFieldsIfFieldIsUnknown()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldByNameForUser(101, "field", $this->user)->returns(null);

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array('field'));
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itCollectsNonExistentFieldsIfFieldIsAMetadataButUnknown()
    {
        $expr = new EqualComparison(new Metadata('summary'), new SimpleValueWrapper('value'));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array('@summary'));
        $this->assertEqual($this->invalid_searchables_collection->getInvalidSearchableErrors(), array());
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotText()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(anOpenListField()->withName('openlist')->build());

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'openlist' is not supported./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumeric()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(anOpenListField()->withName('openlist')->build());

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper(20));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'openlist' is not supported./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotDate()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(anOpenListField()->withName('openlist')->build());

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('2017-01-17'));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'openlist' is not supported./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotClosedList()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(anOpenListField()->withName('openlist')->build());

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('planned'));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'openlist' is not supported./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanComparison()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(aStringField()->withName('string')->build());

        $expr = new LesserThanComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'string' is not supported for the operator <./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanComparison()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(aStringField()->withName('string')->build());

        $expr = new GreaterThanComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'string' is not supported for the operator >./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanOrEqualComparison()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(aStringField()->withName('string')->build());

        $expr = new LesserThanOrEqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'string' is not supported for the operator <=./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanOrEqualComparison()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(aStringField()->withName('string')->build());

        $expr = new GreaterThanOrEqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'string' is not supported for the operator >=./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotNumericForBetweenComparison()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(aStringField()->withName('string')->build());

        $expr = new BetweenComparison(
            new Field('field'),
            new BetweenValueWrapper(
                new SimpleValueWrapper('value1'),
                new SimpleValueWrapper('value2')
            )
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'string' is not supported for the operator between()./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotListForInComparison()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(aStringField()->withName('string')->build());

        $expr = new InComparison(
            new Field('field'),
            new InValueWrapper(
                array(
                    new SimpleValueWrapper('value1'),
                    new SimpleValueWrapper('value2')
                )
            )
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'string' is not supported for the operator in()./", implode("\n", $errors));
    }

    public function itCollectsUnsupportedFieldsIfFieldIsNotListForNotInComparison()
    {
        stub($this->formelement_factory)
            ->getUsedFormElementFieldByNameForUser(101, "field", $this->user)
            ->returns(aStringField()->withName('string')->build());

        $expr = new NotInComparison(
            new Field('field'),
            new InValueWrapper(
                array(
                    new SimpleValueWrapper('value3'),
                    new SimpleValueWrapper('value4')
                )
            )
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEqual($this->invalid_searchables_collection->getNonexistentSearchables(), array());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertPattern("/The field 'string' is not supported for the operator not in()./", implode("\n", $errors));
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
