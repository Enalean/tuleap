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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Logical;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parenthesis;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapper;
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

final class InvalidTermCollectorVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const UNSUPPORTED_FIELD_NAME = 'openlist';
    private const FIELD_NAME             = 'lackwittedly';
    private const STRING_FIELD_NAME      = 'string';
    private const TRACKER_ID             = 101;
    private \Tracker_FormElement_Field_String $string_field;
    private \Tracker_FormElement_Field_List $open_list_field;
    private \Tracker_FormElement_Field_Text $field_text;
    private \Tracker_FormElement_Field_Integer $int_field;
    private \Tracker_FormElementFactory & MockObject $formelement_factory;
    private \PFUser $user;
    private \Tracker $tracker;
    private InvalidSearchablesCollection $invalid_searchable_collection;
    private Comparison $comparison;
    private ?Logical $parsed_query;

    protected function setUp(): void
    {
        $this->tracker         = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();
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

        $this->comparison   = new EqualComparison(new Field(self::STRING_FIELD_NAME), new SimpleValueWrapper('value'));
        $this->parsed_query = null;

        $this->invalid_searchable_collection = new InvalidSearchablesCollection();
    }

    private function check(): void
    {
        $collector = new InvalidTermCollectorVisitor(
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
            new InvalidSearchableCollectorVisitor($this->formelement_factory, $this->tracker, $this->user)
        );
        $collector->collectErrors(
            $this->parsed_query ?? new AndExpression($this->comparison),
            $this->invalid_searchable_collection,
        );
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, 'field', $this->user)
            ->willReturn($this->field_text);

        $this->comparison = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfDateFieldIsUsedForEqualComparison(): void
    {
        $date_field = TrackerFormElementDateFieldBuilder::aDateField(104)->withTime()->build();
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, "field", $this->user)
            ->willReturn($date_field);

        $this->comparison = new EqualComparison(new Field('field'), new SimpleValueWrapper('2017-01-17'));

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForNotEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, "field", $this->user)
            ->willReturn($this->field_text);

        $this->comparison = new NotEqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, "int", $this->user)
            ->willReturn($this->int_field);

        $this->comparison = new LesserThanComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, "int", $this->user)
            ->willReturn($this->int_field);

        $this->comparison = new GreaterThanComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForLesserThanOrEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, "int", $this->user)
            ->willReturn($this->int_field);

        $this->comparison = new LesserThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForGreaterThanOrEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, "int", $this->user)
            ->willReturn($this->int_field);

        $this->comparison = new GreaterThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(20));

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItDoesNotCollectInvalidFieldsIfFieldIsUsedForBetweenComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, "int", $this->user)
            ->willReturn($this->int_field);

        $this->comparison = new BetweenComparison(
            new Field('int'),
            new BetweenValueWrapper(
                new SimpleValueWrapper(20),
                new SimpleValueWrapper(30)
            )
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsNonExistentFieldsIfFieldIsUnknown(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, "field", $this->user)
            ->willReturn(null);

        $this->comparison = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->check();
        self::assertEquals(['field'], $this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsNonExistentFieldsIfFieldIsAMetadataButUnknown(): void
    {
        $this->comparison = new EqualComparison(new Metadata('summary'), new SimpleValueWrapper('value'));

        $this->check();
        self::assertEquals(['@summary'], $this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotText(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::UNSUPPORTED_FIELD_NAME, $this->user)
            ->willReturn($this->open_list_field);

        $this->comparison = new EqualComparison(
            new Field(self::UNSUPPORTED_FIELD_NAME),
            new SimpleValueWrapper('value')
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported.", self::UNSUPPORTED_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumeric(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::UNSUPPORTED_FIELD_NAME, $this->user)
            ->willReturn($this->open_list_field);

        $this->comparison = new EqualComparison(new Field(self::UNSUPPORTED_FIELD_NAME), new SimpleValueWrapper(20));

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported.", self::UNSUPPORTED_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotDate(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::UNSUPPORTED_FIELD_NAME, $this->user)
            ->willReturn($this->open_list_field);

        $this->comparison = new EqualComparison(
            new Field(self::UNSUPPORTED_FIELD_NAME),
            new SimpleValueWrapper('2017-01-17')
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported.", self::UNSUPPORTED_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotClosedList(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::UNSUPPORTED_FIELD_NAME, $this->user)
            ->willReturn($this->open_list_field);

        $this->comparison = new EqualComparison(
            new Field(self::UNSUPPORTED_FIELD_NAME),
            new SimpleValueWrapper('planned')
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported.", self::UNSUPPORTED_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $this->comparison = new LesserThanComparison(
            new Field(self::STRING_FIELD_NAME),
            new SimpleValueWrapper('value')
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator <.", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $this->comparison = new GreaterThanComparison(
            new Field(self::STRING_FIELD_NAME),
            new SimpleValueWrapper('value')
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator >.", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForLesserThanOrEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $this->comparison = new LesserThanOrEqualComparison(
            new Field(self::STRING_FIELD_NAME),
            new SimpleValueWrapper('value')
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator <=.", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForGreaterThanOrEqualComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $this->comparison = new GreaterThanOrEqualComparison(
            new Field(self::STRING_FIELD_NAME),
            new SimpleValueWrapper('value')
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator >=.", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotNumericForBetweenComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $this->comparison = new BetweenComparison(
            new Field(self::STRING_FIELD_NAME),
            new BetweenValueWrapper(
                new SimpleValueWrapper('value1'),
                new SimpleValueWrapper('value2')
            )
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator between().", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotListForInComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $this->comparison = new InComparison(
            new Field(self::STRING_FIELD_NAME),
            new InValueWrapper(
                [
                    new SimpleValueWrapper('value1'),
                    new SimpleValueWrapper('value2'),
                ]
            )
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator in().", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    public function testItCollectsUnsupportedFieldsIfFieldIsNotListForNotInComparison(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::STRING_FIELD_NAME, $this->user)
            ->willReturn($this->string_field);

        $this->comparison = new NotInComparison(
            new Field(self::STRING_FIELD_NAME),
            new InValueWrapper(
                [
                    new SimpleValueWrapper('value3'),
                    new SimpleValueWrapper('value4'),
                ]
            )
        );

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());

        $errors = $this->invalid_searchable_collection->getInvalidSearchableErrors();
        self::assertStringContainsString(
            sprintf("The field '%s' is not supported for the operator not in().", self::STRING_FIELD_NAME),
            implode("\n", $errors)
        );
    }

    private static function generateInvalidComparisonsForFieldsThatAreNotLists(
        Field $field,
        ValueWrapper $valid_value,
    ): iterable {
        $open = new StatusOpenValueWrapper();
        yield '= OPEN()' => [new EqualComparison($field, $open)];
        yield 'IN()' => [new InComparison($field, new InValueWrapper([$valid_value]))];
        yield 'NOT IN()' => [new NotInComparison($field, new InValueWrapper([$valid_value]))];
    }

    public static function generateInvalidNumericComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $empty_value = new SimpleValueWrapper('');
        $valid_value = new SimpleValueWrapper(10.5);
        $now         = new CurrentDateTimeValueWrapper(null, null);
        yield '< empty string' => [new LesserThanComparison($field, $empty_value)];
        yield '<= empty string' => [new LesserThanOrEqualComparison($field, $empty_value)];
        yield '> empty string' => [new GreaterThanComparison($field, $empty_value)];
        yield '>= empty string' => [new GreaterThanOrEqualComparison($field, $empty_value)];
        yield "BETWEEN('', 10.5)" => [
            new BetweenComparison($field, new BetweenValueWrapper($empty_value, $valid_value)),
        ];
        yield "BETWEEN(10.5, '')" => [
            new BetweenComparison($field, new BetweenValueWrapper($valid_value, $empty_value)),
        ];
        yield '= NOW()' => [new EqualComparison($field, $now)];
        yield '= string value' => [new EqualComparison($field, new SimpleValueWrapper('string'))];
        foreach (self::generateInvalidComparisonsForFieldsThatAreNotLists($field, $valid_value) as $case) {
            yield $case;
        }
    }

    /**
     * @dataProvider generateInvalidNumericComparisons
     */
    public function testItRejectsInvalidFloatComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                TrackerFormElementFloatFieldBuilder::aFloatField(186)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidNumericComparisons
     */
    public function testItRejectsInvalidIntComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                TrackerFormElementIntFieldBuilder::anIntField(479)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidTextComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper('Graphium');
        $now         = new CurrentDateTimeValueWrapper(null, null);
        yield '< anything' => [new LesserThanComparison($field, $valid_value)];
        yield '<= anything' => [new LesserThanOrEqualComparison($field, $valid_value)];
        yield '> anything' => [new GreaterThanComparison($field, $valid_value)];
        yield '>= anything' => [new GreaterThanOrEqualComparison($field, $valid_value)];
        yield 'BETWEEN anything' => [
            new BetweenComparison($field, new BetweenValueWrapper($valid_value, $valid_value)),
        ];
        yield '= NOW()' => [new EqualComparison($field, $now)];
        foreach (self::generateInvalidComparisonsForFieldsThatAreNotLists($field, $valid_value) as $case) {
            yield $case;
        }
    }

    /**
     * @dataProvider generateInvalidTextComparisons
     */
    public function testItRejectsInvalidStringComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                TrackerFormElementStringFieldBuilder::aStringField(975)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidTextComparisons
     */
    public function testItRejectsInvalidTextComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                TrackerFormElementTextFieldBuilder::aTextField(612)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateFieldTypes(): iterable
    {
        yield 'int' => [TrackerFormElementIntFieldBuilder::anIntField(132)->withName(self::FIELD_NAME)->build()];
        yield 'float' => [TrackerFormElementFloatFieldBuilder::aFloatField(202)->withName(self::FIELD_NAME)->build()];
        yield 'string' => [TrackerFormElementStringFieldBuilder::aStringField(716)->withName(self::FIELD_NAME)->build()];
        yield 'text' => [TrackerFormElementTextFieldBuilder::aTextField(198)->withName(self::FIELD_NAME)->build()];
    }

    /**
     * @dataProvider generateFieldTypes
     */
    public function testItRejectsInvalidComparisonToMyself(\Tracker_FormElement_Field $field): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')->willReturn($field);
        $user_manager = $this->createStub(\UserManager::class);
        $user_manager->method('getCurrentUser')->willReturn($this->user);

        $this->comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new CurrentUserValueWrapper($user_manager)
        );

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateNestedExpressions(): iterable
    {
        $valid_comparison   = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper(5));
        $invalid_comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper('string value')
        );
        yield 'AndOperand' => [new AndExpression($valid_comparison, new AndOperand($invalid_comparison))];
        yield 'Tail of AndOperand' => [new AndExpression(
            $valid_comparison,
            new AndOperand($valid_comparison, new AndOperand($invalid_comparison))
        ),
        ];
        yield 'OrExpression' => [new OrExpression(new AndExpression($invalid_comparison))];
        yield 'OrOperand' => [new OrExpression(
            new AndExpression($valid_comparison),
            new OrOperand(new AndExpression($invalid_comparison))
        ),
        ];
        yield 'Tail of OrOperand' => [new OrExpression(
            new AndExpression($valid_comparison),
            new OrOperand(
                new AndExpression($valid_comparison),
                new OrOperand(new AndExpression($invalid_comparison))
            )
        ),
        ];
        yield 'Parenthesis' => [new AndExpression(
            new Parenthesis(
                new OrExpression(new AndExpression($invalid_comparison))
            )
        ),
        ];
    }

    /**
     * @dataProvider generateNestedExpressions
     */
    public function testItAddsInvalidFieldInNestedExpressions(Logical $parsed_query): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturnOnConsecutiveCalls(
                $this->int_field,
                $this->int_field,
                $this->int_field
            );
        $this->parsed_query = $parsed_query;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }
}
