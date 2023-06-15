<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
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
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\InComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\LesserThanComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\LesserThanOrEqualComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\NotEqualComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\NotInComparisonChecker;

final class InvalidSearchablesCollectorVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker_FormElement_Field_String
     */
    private $string_field;

    /**
     * @var \Tracker_FormElement_Field_OpenList
     */
    private $open_list_field;
    /**
     * @var \Tracker_FormElement_Field_Text
     */
    private $field_text;
    /**
     * @var \Tracker_FormElement_Field_Integer
     */
    private $int_field;
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;
    /** @var InvalidTermCollectorVisitor */
    private $collector;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var InvalidComparisonCollectorParameters
     */
    private $parameters;
    /** @var InvalidSearchablesCollection */
    private $invalid_searchables_collection;

    protected function setUp(): void
    {
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $this->field_text = new Tracker_FormElement_Field_Text(
            101,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );
        $this->int_field  = new Tracker_FormElement_Field_Integer(
            102,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $this->open_list_field = new \Tracker_FormElement_Field_OpenList(
            102,
            null,
            null,
            'openlist',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $this->string_field = new \Tracker_FormElement_Field_String(
            102,
            null,
            null,
            'string',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->user                = new PFUser(['language_id' => 'en']);

        $this->invalid_searchables_collection = new InvalidSearchablesCollection();
        $this->parameters                     = new InvalidComparisonCollectorParameters(
            $this->invalid_searchables_collection
        );

        $this->collector = new InvalidTermCollectorVisitor(
            new EqualComparisonVisitor(),
            new NotEqualComparisonVisitor(),
            new LesserThanComparisonVisitor(),
            new GreaterThanComparisonVisitor(),
            new LesserThanOrEqualComparisonVisitor(),
            new GreaterThanOrEqualComparisonVisitor(),
            new BetweenComparisonVisitor(),
            new InComparisonVisitor(),
            new NotInComparisonVisitor(),
            new InvalidFields\ArtifactLink\ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao(),
                ),
            ),
            new EqualComparisonChecker(),
            new NotEqualComparisonChecker(),
            new LesserThanComparisonChecker(),
            new GreaterThanComparisonChecker(),
            new LesserThanOrEqualComparisonChecker(),
            new BetweenComparisonChecker(),
            new InComparisonChecker(),
            new NotInComparisonChecker(),
            new InvalidSearchableCollectorVisitor($this->formelement_factory, $tracker, $this->user)
        );
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForEqualComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->field_text);

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->visitEqualComparison($expr, $this->parameters);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfDateFieldIsUsedForEqualComparison(): void
    {
        $date_field = \Mockery::mock(Tracker_FormElement_Field_Date::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $date_field->shouldReceive('isTimeDisplayed')->andReturn(true);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($date_field);

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('2017-01-17'));

        $this->collector->visitEqualComparison($expr, $this->parameters);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForNotEqualComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->field_text);

        $expr = new NotEqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->collector->visitNotEqualComparison($expr, $this->parameters);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)->andReturns($this->int_field);

        $expr = new LesserThanComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->collector->visitLesserThanComparison($expr, $this->parameters);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)->andReturns($this->int_field);

        $expr = new GreaterThanComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->collector->visitGreaterThanComparison($expr, $this->parameters);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanOrEqualComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)->andReturns($this->int_field);

        $expr = new LesserThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->collector->visitLesserThanOrEqualComparison($expr, $this->parameters);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanOrEqualComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)->andReturns($this->int_field);

        $expr = new GreaterThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->collector->visitGreaterThanOrEqualComparison($expr, $this->parameters);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForBetweenComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)->andReturns($this->int_field);

        $expr = new BetweenComparison(
            new Field('int'),
            new BetweenValueWrapper(
                new SimpleValueWrapper(20),
                new SimpleValueWrapper(30)
            )
        );

        $this->collector->visitBetweenComparison($expr, $this->parameters);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsNonExistentFieldsIfFieldIsUnknown(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns(null);

        $expr = new AndExpression(new EqualComparison(new Field('field'), new SimpleValueWrapper('value')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals(['field'], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsNonExistentFieldsIfFieldIsAMetadataButUnknown(): void
    {
        $expr = new AndExpression(new EqualComparison(new Metadata('summary'), new SimpleValueWrapper('value')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals(['@summary'], $this->invalid_searchables_collection->getNonexistentSearchables());
        $this->assertEquals([], $this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotText(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->open_list_field);

        $expr = new AndExpression(new EqualComparison(new Field('field'), new SimpleValueWrapper('value')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'openlist' is not supported.", implode("\n", $errors));
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumeric(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->open_list_field);

        $expr = new AndExpression(new EqualComparison(new Field('field'), new SimpleValueWrapper(20)));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'openlist' is not supported.", implode("\n", $errors));
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotDate(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->open_list_field);

        $expr = new AndExpression(new EqualComparison(new Field('field'), new SimpleValueWrapper('2017-01-17')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'openlist' is not supported.", implode("\n", $errors));
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotClosedList(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->open_list_field);

        $expr = new AndExpression(new EqualComparison(new Field('field'), new SimpleValueWrapper('planned')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'openlist' is not supported.", implode("\n", $errors));
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->string_field);

        $expr = new AndExpression(new LesserThanComparison(new Field('field'), new SimpleValueWrapper('value')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'string' is not supported for the operator <.", implode("\n", $errors));
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->string_field);

        $expr = new AndExpression(new GreaterThanComparison(new Field('field'), new SimpleValueWrapper('value')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'string' is not supported for the operator >.", implode("\n", $errors));
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanOrEqualComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->string_field);

        $expr = new AndExpression(new LesserThanOrEqualComparison(new Field('field'), new SimpleValueWrapper('value')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'string' is not supported for the operator <=.", implode("\n", $errors));
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanOrEqualComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->string_field);

        $expr = new AndExpression(new GreaterThanOrEqualComparison(new Field('field'), new SimpleValueWrapper('value')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'string' is not supported for the operator >=.", implode("\n", $errors));
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForBetweenComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->string_field);

        $expr = new AndExpression(
            new BetweenComparison(
                new Field('field'),
                new BetweenValueWrapper(
                    new SimpleValueWrapper('value1'),
                    new SimpleValueWrapper('value2')
                )
            )
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString(
            "The field 'string' is not supported for the operator between().",
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotListForInComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->string_field);

        $expr = new AndExpression(
            new InComparison(
                new Field('field'),
                new InValueWrapper(
                    [
                        new SimpleValueWrapper('value1'),
                        new SimpleValueWrapper('value2'),
                    ]
                )
            )
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'string' is not supported for the operator in().", implode("\n", $errors));
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotListForNotInComparison(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)->andReturns($this->string_field);

        $expr = new AndExpression(
            new NotInComparison(
                new Field('field'),
                new InValueWrapper(
                    [
                        new SimpleValueWrapper('value3'),
                        new SimpleValueWrapper('value4'),
                    ]
                )
            )
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        $this->assertEquals([], $this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        $this->assertStringContainsString("The field 'string' is not supported for the operator not in().", implode("\n", $errors));
    }

    public function testItDelegatesValidationToSubExpressionAndTailInAndExpression(): void
    {
        $subexpression = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison::class);
        $tail          = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand::class);
        $expression    = new AndExpression($subexpression, $tail);

        $subexpression->shouldReceive('acceptTermVisitor')->with($this->collector, $this->parameters)->once();
        $tail->shouldReceive('acceptLogicalVisitor')->with($this->collector, $this->parameters)->once();

        $this->collector->visitAndExpression($expression, $this->parameters);
    }

    public function testItDelegatesValidationToSubExpressionAndTailInOrExpression(): void
    {
        $subexpression = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression::class);
        $tail          = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand::class);
        $expression    = new OrExpression($subexpression, $tail);

        $subexpression->shouldReceive('acceptLogicalVisitor')->with($this->collector, $this->parameters)->once();
        $tail->shouldReceive('acceptLogicalVisitor')->with($this->collector, $this->parameters)->once();

        $this->collector->visitOrExpression($expression, $this->parameters);
    }

    public function testItDelegatesValidationToOperandAndTailInOrOperand(): void
    {
        $operand    = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression::class);
        $tail       = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand::class);
        $expression = new OrOperand($operand, $tail);

        $operand->shouldReceive('acceptLogicalVisitor')->with($this->collector, $this->parameters)->once();
        $tail->shouldReceive('acceptLogicalVisitor')->with($this->collector, $this->parameters)->once();

        $this->collector->visitOrOperand($expression, $this->parameters);
    }

    public function testItDelegatesValidationToOperandAndTailInAndOperand(): void
    {
        $operand    = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison::class);
        $tail       = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand::class);
        $expression = new AndOperand($operand, $tail);

        $operand->shouldReceive('acceptTermVisitor')->with($this->collector, $this->parameters)->once();
        $tail->shouldReceive('acceptLogicalVisitor')->with($this->collector, $this->parameters)->once();

        $this->collector->visitAndOperand($expression, $this->parameters);
    }

    public function testItDelegatesValidationToSubExpressionInAndExpression(): void
    {
        $subexpression = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison::class);
        $tail          = null;
        $expression    = new AndExpression($subexpression, $tail);

        $subexpression->shouldReceive('acceptTermVisitor')->with($this->collector, $this->parameters)->once();

        $this->collector->visitAndExpression($expression, $this->parameters);
    }

    public function testItDelegatesValidationToSubExpressionInOrExpression(): void
    {
        $subexpression = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression::class);
        $tail          = null;
        $expression    = new OrExpression($subexpression, $tail);

        $subexpression->shouldReceive('acceptLogicalVisitor')->with($this->collector, $this->parameters)->once();

        $this->collector->visitOrExpression($expression, $this->parameters);
    }

    public function testItDelegatesValidationToOperandInOrOperand(): void
    {
        $operand    = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression::class);
        $tail       = null;
        $expression = new OrOperand($operand, $tail);

        $operand->shouldReceive('acceptLogicalVisitor')->with($this->collector, $this->parameters)->once();

        $this->collector->visitOrOperand($expression, $this->parameters);
    }

    public function testItDelegatesValidationToOperandInAndOperand(): void
    {
        $operand    = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison::class);
        $tail       = null;
        $expression = new AndOperand($operand, $tail);

        $operand->shouldReceive('acceptTermVisitor')->with($this->collector, $this->parameters)->once();

        $this->collector->visitAndOperand($expression, $this->parameters);
    }
}
