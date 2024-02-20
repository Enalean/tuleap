<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
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
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementFloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementOpenListBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementTextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class InvalidSearchablesCollectorVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const UNSUPPORTED_FIELD_NAME = 'openlist';
    private const STRING_FIELD_NAME      = 'string';
    private const INVALID_FIELD_NAME     = 'invalid';
    private \Tracker_FormElement_Field_String $string_field;
    private \Tracker_FormElement_Field_List $open_list_field;
    private \Tracker_FormElement_Field_Text $field_text;
    private \Tracker_FormElement_Field_Integer $int_field;
    private \Tracker_FormElementFactory & MockObject $formelement_factory;
    private InvalidTermCollectorVisitor $collector;
    private \PFUser $user;
    private InvalidComparisonCollectorParameters $parameters;
    private InvalidSearchablesCollection $invalid_searchables_collection;

    protected function setUp(): void
    {
        $tracker               = TrackerTestBuilder::aTracker()->withId(101)->build();
        $this->field_text      = TrackerFormElementTextFieldBuilder::aTextField(101)->build();
        $this->int_field       = TrackerFormElementIntFieldBuilder::anIntField(102)->build();
        $this->open_list_field = TrackerFormElementOpenListBuilder::aBind()
            ->withId(102)
            ->withName(self::UNSUPPORTED_FIELD_NAME)
            ->buildStaticBind()
            ->getField();
        $this->string_field    = TrackerFormElementStringFieldBuilder::aStringField(103)
            ->withName(self::STRING_FIELD_NAME)
            ->build();

        $this->formelement_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->user                = UserTestBuilder::buildWithDefaults();

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
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)
            ->willReturn($this->field_text);

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $expr->acceptTermVisitor($this->collector, $this->parameters);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfDateFieldIsUsedForEqualComparison(): void
    {
        $date_field = TrackerFormElementDateFieldBuilder::aDateField(104)->withTime()->build();
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)
            ->willReturn($date_field);

        $expr = new EqualComparison(new Field('field'), new SimpleValueWrapper('2017-01-17'));

        $expr->acceptTermVisitor($this->collector, $this->parameters);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForNotEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)
            ->willReturn($this->field_text);

        $expr = new NotEqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $expr->acceptTermVisitor($this->collector, $this->parameters);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)
            ->willReturn($this->int_field);

        $expr = new LesserThanComparison(new Field('int'), new SimpleValueWrapper(20));

        $expr->acceptTermVisitor($this->collector, $this->parameters);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)
            ->willReturn($this->int_field);

        $expr = new GreaterThanComparison(new Field('int'), new SimpleValueWrapper(20));

        $expr->acceptTermVisitor($this->collector, $this->parameters);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanOrEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)
            ->willReturn($this->int_field);

        $expr = new LesserThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(20));

        $expr->acceptTermVisitor($this->collector, $this->parameters);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanOrEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)
            ->willReturn($this->int_field);

        $expr = new GreaterThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(20));

        $expr->acceptTermVisitor($this->collector, $this->parameters);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForBetweenComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, "int", $this->user)
            ->willReturn($this->int_field);

        $expr = new BetweenComparison(
            new Field('int'),
            new BetweenValueWrapper(
                new SimpleValueWrapper(20),
                new SimpleValueWrapper(30)
            )
        );

        $expr->acceptTermVisitor($this->collector, $this->parameters);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsNonExistentFieldsIfFieldIsUnknown(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, "field", $this->user)
            ->willReturn(null);

        $expr = new AndExpression(new EqualComparison(new Field('field'), new SimpleValueWrapper('value')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEquals(['field'], $this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsNonExistentFieldsIfFieldIsAMetadataButUnknown(): void
    {
        $expr = new AndExpression(new EqualComparison(new Metadata('summary'), new SimpleValueWrapper('value')));

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEquals(['@summary'], $this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotText(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::UNSUPPORTED_FIELD_NAME, $this->user)
            ->willReturn($this->open_list_field);

        $expr = new AndExpression(
            new EqualComparison(new Field(self::UNSUPPORTED_FIELD_NAME), new SimpleValueWrapper('value'))
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported.", self::UNSUPPORTED_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumeric(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::UNSUPPORTED_FIELD_NAME, $this->user)
            ->willReturn($this->open_list_field);

        $expr = new AndExpression(
            new EqualComparison(new Field(self::UNSUPPORTED_FIELD_NAME), new SimpleValueWrapper(20))
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported.", self::UNSUPPORTED_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotDate(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::UNSUPPORTED_FIELD_NAME, $this->user)
            ->willReturn($this->open_list_field);

        $expr = new AndExpression(
            new EqualComparison(new Field(self::UNSUPPORTED_FIELD_NAME), new SimpleValueWrapper('2017-01-17'))
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported.", self::UNSUPPORTED_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotClosedList(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::UNSUPPORTED_FIELD_NAME, $this->user)
            ->willReturn($this->open_list_field);

        $expr = new AndExpression(
            new EqualComparison(new Field(self::UNSUPPORTED_FIELD_NAME), new SimpleValueWrapper('planned'))
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported.", self::UNSUPPORTED_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $expr = new AndExpression(
            new LesserThanComparison(new Field(self::STRING_FIELD_NAME), new SimpleValueWrapper('value'))
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator <.", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $expr = new AndExpression(
            new GreaterThanComparison(new Field(self::STRING_FIELD_NAME), new SimpleValueWrapper('value'))
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator >.", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanOrEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $expr = new AndExpression(
            new LesserThanOrEqualComparison(new Field(self::STRING_FIELD_NAME), new SimpleValueWrapper('value'))
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator <=.", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanOrEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $expr = new AndExpression(
            new GreaterThanOrEqualComparison(new Field(self::STRING_FIELD_NAME), new SimpleValueWrapper('value'))
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator >=.", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForBetweenComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $expr = new AndExpression(
            new BetweenComparison(
                new Field(self::STRING_FIELD_NAME),
                new BetweenValueWrapper(
                    new SimpleValueWrapper('value1'),
                    new SimpleValueWrapper('value2')
                )
            )
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator between().", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotListForInComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $expr = new AndExpression(
            new InComparison(
                new Field(self::STRING_FIELD_NAME),
                new InValueWrapper(
                    [
                        new SimpleValueWrapper('value1'),
                        new SimpleValueWrapper('value2'),
                    ]
                )
            )
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator in().", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotListForNotInComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(101, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $expr = new AndExpression(
            new NotInComparison(
                new Field(self::STRING_FIELD_NAME),
                new InValueWrapper(
                    [
                        new SimpleValueWrapper('value3'),
                        new SimpleValueWrapper('value4'),
                    ]
                )
            )
        );

        $this->collector->collectErrors($expr, $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchables_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator not in().", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public static function generateInvalidFloatComparisons(): iterable
    {
        $field       = new Field(self::INVALID_FIELD_NAME);
        $empty_value = new SimpleValueWrapper('');
        $valid_value = new SimpleValueWrapper(10.5);
        $now         = new CurrentDateTimeValueWrapper(null, null);

        $open = new StatusOpenValueWrapper();
        yield [new LesserThanComparison($field, $empty_value)];
        yield [new LesserThanOrEqualComparison($field, $empty_value)];
        yield [new GreaterThanComparison($field, $empty_value)];
        yield [new GreaterThanOrEqualComparison($field, $empty_value)];
        yield [new BetweenComparison($field, new BetweenValueWrapper($empty_value, $valid_value))];
        yield [new BetweenComparison($field, new BetweenValueWrapper($valid_value, $empty_value))];
        yield [new EqualComparison($field, new SimpleValueWrapper('string'))];
        yield [new EqualComparison($field, $now)];
        yield [new EqualComparison($field, $open)];
        yield [new InComparison($field, new InValueWrapper([$valid_value]))];
        yield [new NotInComparison($field, new InValueWrapper([$valid_value]))];
    }

    /**
     * @dataProvider generateInvalidFloatComparisons
     */
    public function testItRejectsInvalidComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                TrackerFormElementFloatFieldBuilder::aFloatField(186)
                    ->withName(self::INVALID_FIELD_NAME)
                    ->build()
            );

        $this->collector->collectErrors(new AndExpression($comparison), $this->invalid_searchables_collection);

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertNotEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItRejectsInvalidComparisonToMyself(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                TrackerFormElementFloatFieldBuilder::aFloatField(186)
                    ->withName(self::INVALID_FIELD_NAME)
                    ->build()
            );
        $user_manager = $this->createStub(\UserManager::class);
        $user_manager->method('getCurrentUser')->willReturn($this->user);

        $this->collector->collectErrors(
            new AndExpression(
                new EqualComparison(
                    new Field(self::INVALID_FIELD_NAME),
                    new CurrentUserValueWrapper($user_manager)
                )
            ),
            $this->invalid_searchables_collection
        );

        self::assertEmpty($this->invalid_searchables_collection->getNonexistentSearchables());
        self::assertNotEmpty($this->invalid_searchables_collection->getInvalidSearchableErrors());
    }

    public function testItDelegatesValidationToSubExpressionAndTailInAndExpression(): void
    {
        $subexpression = $this->createMock(EqualComparison::class);
        $tail          = $this->createMock(AndOperand::class);
        $expression    = new AndExpression($subexpression, $tail);

        $subexpression->expects(self::once())->method('acceptTermVisitor')->with($this->collector, $this->parameters);
        $tail->expects(self::once())->method('acceptLogicalVisitor')->with($this->collector, $this->parameters);

        $expression->acceptLogicalVisitor($this->collector, $this->parameters);
    }

    public function testItDelegatesValidationToSubExpressionAndTailInOrExpression(): void
    {
        $subexpression = $this->createMock(AndExpression::class);
        $tail          = $this->createMock(OrOperand::class);
        $expression    = new OrExpression($subexpression, $tail);

        $subexpression->expects(self::once())->method('acceptLogicalVisitor')->with(
            $this->collector,
            $this->parameters
        );
        $tail->expects(self::once())->method('acceptLogicalVisitor')->with($this->collector, $this->parameters);

        $expression->acceptLogicalVisitor($this->collector, $this->parameters);
    }

    public function testItDelegatesValidationToOperandAndTailInOrOperand(): void
    {
        $operand    = $this->createMock(AndExpression::class);
        $tail       = $this->createMock(OrOperand::class);
        $expression = new OrOperand($operand, $tail);

        $operand->expects(self::once())->method('acceptLogicalVisitor')->with($this->collector, $this->parameters);
        $tail->expects(self::once())->method('acceptLogicalVisitor')->with($this->collector, $this->parameters);

        $expression->acceptLogicalVisitor($this->collector, $this->parameters);
    }

    public function testItDelegatesValidationToOperandAndTailInAndOperand(): void
    {
        $operand    = $this->createMock(EqualComparison::class);
        $tail       = $this->createMock(AndOperand::class);
        $expression = new AndOperand($operand, $tail);

        $operand->expects(self::once())->method('acceptTermVisitor')->with($this->collector, $this->parameters);
        $tail->expects(self::once())->method('acceptLogicalVisitor')->with($this->collector, $this->parameters);

        $this->collector->visitAndOperand($expression, $this->parameters);
    }

    public function testItDelegatesValidationToSubExpressionInAndExpression(): void
    {
        $subexpression = $this->createMock(EqualComparison::class);
        $tail          = null;
        $expression    = new AndExpression($subexpression, $tail);

        $subexpression->expects(self::once())->method('acceptTermVisitor')->with($this->collector, $this->parameters);

        $expression->acceptLogicalVisitor($this->collector, $this->parameters);
    }

    public function testItDelegatesValidationToSubExpressionInOrExpression(): void
    {
        $subexpression = $this->createMock(AndExpression::class);
        $tail          = null;
        $expression    = new OrExpression($subexpression, $tail);

        $subexpression->expects(self::once())->method('acceptLogicalVisitor')->with(
            $this->collector,
            $this->parameters
        );

        $expression->acceptLogicalVisitor($this->collector, $this->parameters);
    }

    public function testItDelegatesValidationToOperandInOrOperand(): void
    {
        $operand    = $this->createMock(AndExpression::class);
        $tail       = null;
        $expression = new OrOperand($operand, $tail);

        $operand->expects(self::once())->method('acceptLogicalVisitor')->with($this->collector, $this->parameters);

        $this->collector->visitOrOperand($expression, $this->parameters);
    }

    public function testItDelegatesValidationToOperandInAndOperand(): void
    {
        $operand    = $this->createMock(EqualComparison::class);
        $tail       = null;
        $expression = new AndOperand($operand, $tail);

        $operand->expects(self::once())->method('acceptTermVisitor')->with($this->collector, $this->parameters);

        $this->collector->visitAndOperand($expression, $this->parameters);
    }
}
