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
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
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
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractorForOpenList;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\BetweenComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\EqualComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\GreaterThanComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\InComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\LesserThanComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\LesserThanOrEqualComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\NotEqualComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\NotInComparisonChecker;
use Tuleap\Tracker\Test\Builders\Fields\CheckboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\LastUpdateByFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\LastUpdateDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\RadioButtonFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedByFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedOnFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class InvalidTermCollectorVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use LegacyTabTranslationsSupport;

    private const UNSUPPORTED_FIELD_NAME = 'openlist';
    private const FIELD_NAME             = 'lackwittedly';
    private const STRING_FIELD_NAME      = 'string';
    private const TRACKER_ID             = 101;
    private \Tracker_FormElementFactory & MockObject $formelement_factory;
    private \PFUser $user;
    private \Tracker $tracker;
    private InvalidSearchablesCollection $invalid_searchable_collection;
    private Comparison $comparison;
    private ?Logical $parsed_query;
    private \UserManager & Stub $user_manager;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();

        $this->formelement_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->user_manager        = $this->createStub(\UserManager::class);
        $this->user                = UserTestBuilder::buildWithDefaults();

        $this->comparison   = new EqualComparison(new Field(self::STRING_FIELD_NAME), new SimpleValueWrapper('value'));
        $this->parsed_query = null;

        $this->invalid_searchable_collection = new InvalidSearchablesCollection();
    }

    private function check(): void
    {
        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new \BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );

        $collector = new InvalidTermCollectorVisitor(
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
            new InvalidSearchableCollectorVisitor(
                $this->formelement_factory,
                new InvalidFieldChecker(
                    new FloatFieldChecker(),
                    new IntegerFieldChecker(),
                    new TextFieldChecker(),
                    new DateFieldChecker(),
                    new FileFieldChecker(),
                    new ListFieldChecker(
                        $list_field_bind_value_normalizer,
                        $bind_labels_extractor,
                        $ugroup_label_converter
                    ),
                    new ListFieldChecker(
                        $list_field_bind_value_normalizer,
                        new CollectionOfNormalizedBindLabelsExtractorForOpenList(
                            $bind_labels_extractor,
                            new OpenListValueDao(),
                            $list_field_bind_value_normalizer,
                        ),
                        $ugroup_label_converter
                    ),
                    new ArtifactSubmitterChecker($this->user_manager),
                    false,
                ),
                $this->tracker,
                $this->user
            )
        );
        $collector->collectErrors(
            $this->parsed_query ?? new AndExpression($this->comparison),
            $this->invalid_searchable_collection,
        );
    }

    public function testItAllowsValidComparisonAndValue(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::FIELD_NAME, $this->user)
            ->willReturn(TextFieldBuilder::aTextField(101)->build());

        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('value'));

        $this->check();
        self::assertEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsNonExistentFieldsIfFieldIsUnknown(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, 'field', $this->user)
            ->willReturn(null);

        $this->comparison = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $this->check();
        self::assertEquals(['field'], $this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsNonExistentFieldsIfMetadataIsUnknown(): void
    {
        $this->comparison = new EqualComparison(new Metadata('summary'), new SimpleValueWrapper('value'));

        $this->check();
        self::assertEquals(['@summary'], $this->invalid_searchable_collection->getNonexistentSearchables());
        self::assertEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItCollectsUnsupportedField(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->with(self::TRACKER_ID, self::UNSUPPORTED_FIELD_NAME, $this->user)
            ->willReturn(
                OpenListFieldBuilder::anOpenListField()
                    ->withId(102)
                    ->withName(self::UNSUPPORTED_FIELD_NAME)
                    ->build()
            );

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

    private static function generateInvalidComparisonsForFieldsThatAreNotLists(
        Field $field,
        ValueWrapper $valid_value,
    ): iterable {
        $open = new StatusOpenValueWrapper();
        yield '= OPEN()' => [new EqualComparison($field, $open)];
        yield 'IN()' => [new InComparison($field, new InValueWrapper([$valid_value]))];
        yield 'NOT IN()' => [new NotInComparison($field, new InValueWrapper([$valid_value]))];
    }

    private static function generateInvalidNumericComparisonsToEmptyString(Field $field, ValueWrapper $valid_value): iterable
    {
        $empty_value = new SimpleValueWrapper('');
        yield '< empty string' => [new LesserThanComparison($field, $empty_value)];
        yield '<= empty string' => [new LesserThanOrEqualComparison($field, $empty_value)];
        yield '> empty string' => [new GreaterThanComparison($field, $empty_value)];
        yield '>= empty string' => [new GreaterThanOrEqualComparison($field, $empty_value)];
        yield "BETWEEN('', valid value)" => [
            new BetweenComparison($field, new BetweenValueWrapper($empty_value, $valid_value)),
        ];
        yield "BETWEEN(valid value, '')" => [
            new BetweenComparison($field, new BetweenValueWrapper($valid_value, $empty_value)),
        ];
    }

    public static function generateInvalidNumericComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper(10.5);
        $now         = new CurrentDateTimeValueWrapper(null, null);
        yield '= string value' => [new EqualComparison($field, new SimpleValueWrapper('string'))];
        yield '= NOW()' => [new EqualComparison($field, $now)];
        yield from self::generateInvalidNumericComparisonsToEmptyString($field, $valid_value);
        yield from self::generateInvalidComparisonsForFieldsThatAreNotLists($field, $valid_value);
    }

    /**
     * @dataProvider generateInvalidNumericComparisons
     */
    public function testItRejectsInvalidFloatComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                FloatFieldBuilder::aFloatField(186)
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
                IntFieldBuilder::anIntField(479)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    private static function generateInvalidComparisonsForFieldsThatAreNotNumeric(Field $field, SimpleValueWrapper $valid_value): iterable
    {
        yield '< anything' => [new LesserThanComparison($field, $valid_value)];
        yield '<= anything' => [new LesserThanOrEqualComparison($field, $valid_value)];
        yield '> anything' => [new GreaterThanComparison($field, $valid_value)];
        yield '>= anything' => [new GreaterThanOrEqualComparison($field, $valid_value)];
        yield 'BETWEEN anything' => [
            new BetweenComparison($field, new BetweenValueWrapper($valid_value, $valid_value)),
        ];
    }

    public static function generateInvalidTextComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper('Graphium');
        $now         = new CurrentDateTimeValueWrapper(null, null);
        yield '= NOW()' => [new EqualComparison($field, $now)];
        yield from self::generateInvalidComparisonsForFieldsThatAreNotNumeric($field, $valid_value);
        yield from self::generateInvalidComparisonsForFieldsThatAreNotLists($field, $valid_value);
    }

    /**
     * @dataProvider generateInvalidTextComparisons
     */
    public function testItRejectsInvalidStringComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                StringFieldBuilder::aStringField(975)
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
                TextFieldBuilder::aTextField(612)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidDateComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper('2024-02-22');
        yield '= string value' => [new EqualComparison($field, new SimpleValueWrapper('string'))];
        yield from self::generateInvalidNumericComparisonsToEmptyString($field, $valid_value);
        yield from self::generateInvalidComparisonsForFieldsThatAreNotLists($field, $valid_value);
    }

    /**
     * @dataProvider generateInvalidDateComparisons
     */
    public function testItRejectsInvalidDateComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                DateFieldBuilder::aDateField(278)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidDateComparisons
     */
    public function testItRejectsInvalidSubmittedOnComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                SubmittedOnFieldBuilder::aSubmittedOnField(538)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidDateComparisons
     */
    public function testItRejectsInvalidLastUpdateDateComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                LastUpdateDateFieldBuilder::aLastUpdateDateField(837)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItRejectsDateFieldWithoutTimeComparedToDateTime(): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                DateFieldBuilder::aDateField(166)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper('2024-02-22 12:23')
        );

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidTextComparisons
     */
    public function testItRejectsInvalidFileComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                FileFieldBuilder::aFileField(324)
                    ->withName(self::FIELD_NAME)
                    ->build()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    private static function generateInvalidListComparisonsToEmptyString(Field $field, ValueWrapper $valid_value): iterable
    {
        $empty_value = new SimpleValueWrapper('');
        yield "IN('', valid value)" => [
            new InComparison($field, new InValueWrapper([$empty_value, $valid_value])),
        ];
        yield "IN(valid value, '')" => [
            new InComparison($field, new InValueWrapper([$valid_value, $empty_value])),
        ];
        yield "NOT IN('', valid value)" => [
            new NotInComparison($field, new InValueWrapper([$empty_value, $valid_value])),
        ];
        yield "NOT IN(valid value, '')" => [
            new NotInComparison($field, new InValueWrapper([$valid_value, $empty_value])),
        ];
    }

    public static function generateInvalidListComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper('unbait');
        $open        = new StatusOpenValueWrapper();
        $now         = new CurrentDateTimeValueWrapper(null, null);
        yield '= NOW()' => [new EqualComparison($field, $now)];
        yield '= OPEN()' => [new EqualComparison($field, $open)];
        yield from self::generateInvalidListComparisonsToEmptyString($field, $valid_value);
        yield from self::generateInvalidComparisonsForFieldsThatAreNotNumeric($field, $valid_value);
    }

    /**
     * @dataProvider generateInvalidListComparisons
     */
    public function testItRejectsInvalidSelectboxComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                ListStaticBindBuilder::aStaticBind(
                    ListFieldBuilder::aListField(957)->withName(self::FIELD_NAME)->build()
                )->build()->getField()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidListComparisons
     */
    public function testItRejectsInvalidMultiSelectboxComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                ListStaticBindBuilder::aStaticBind(
                    ListFieldBuilder::aListField(957)->withMultipleValues()->withName(self::FIELD_NAME)->build()
                )->build()->getField()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidListComparisons
     */
    public function testItRejectsInvalidRadioButtonComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                ListStaticBindBuilder::aStaticBind(
                    RadioButtonFieldBuilder::aRadioButtonField(334)->withName(self::FIELD_NAME)->build()
                )->build()->getField()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidListComparisons
     */
    public function testItRejectsInvalidCheckboxComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                ListStaticBindBuilder::aStaticBind(
                    CheckboxFieldBuilder::aCheckboxField(81)->withName(self::FIELD_NAME)->build()
                )->build()->getField()
            );
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidSubmitterComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper('rdavis');
        $empty_value = new SimpleValueWrapper('');
        $open        = new StatusOpenValueWrapper();
        $now         = new CurrentDateTimeValueWrapper(null, null);
        yield '= NOW()' => [new EqualComparison($field, $now)];
        yield '= OPEN()' => [new EqualComparison($field, $open)];
        yield '= empty string' => [new EqualComparison($field, $empty_value)];
        yield '!= empty string' => [new NotEqualComparison($field, $empty_value)];
        yield from self::generateInvalidListComparisonsToEmptyString($field, $valid_value);
        yield from self::generateInvalidComparisonsForFieldsThatAreNotNumeric($field, $valid_value);
    }

    /**
     * @dataProvider generateInvalidSubmitterComparisons
     */
    public function testItRejectsInvalidSubmittedByComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                SubmittedByFieldBuilder::aSubmittedByField(928)->withName(self::FIELD_NAME)->build()
            );
        $this->user_manager->method('getUserByLoginName')->willReturn(UserTestBuilder::buildWithDefaults());
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidSubmitterComparisons
     */
    public function testItRejectsInvalidLastUpdateByComparisons(Comparison $comparison): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturn(
                LastUpdateByFieldBuilder::aLastUpdateByField(965)->withName(self::FIELD_NAME)->build()
            );
        $this->user_manager->method('getUserByLoginName')->willReturn(UserTestBuilder::buildWithDefaults());
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateFieldTypes(): iterable
    {
        yield 'int' => [IntFieldBuilder::anIntField(132)->withName(self::FIELD_NAME)->build()];
        yield 'float' => [FloatFieldBuilder::aFloatField(202)->withName(self::FIELD_NAME)->build()];
        yield 'string' => [StringFieldBuilder::aStringField(716)->withName(self::FIELD_NAME)->build()];
        yield 'text' => [TextFieldBuilder::aTextField(198)->withName(self::FIELD_NAME)->build()];
        yield 'date' => [DateFieldBuilder::aDateField(514)->withName(self::FIELD_NAME)->build()];
        yield 'submitted on' => [
            SubmittedOnFieldBuilder::aSubmittedOnField(786)->withName(self::FIELD_NAME)->build(),
        ];
        yield 'last update date' => [
            LastUpdateDateFieldBuilder::aLastUpdateDateField(129)->withName(self::FIELD_NAME)->build(),
        ];
        yield 'file' => [FileFieldBuilder::aFileField(272)->withName(self::FIELD_NAME)->build()];

        $list_field = ListFieldBuilder::aListField(175)->withName(self::FIELD_NAME)->build();

        yield 'static list' => [ListStaticBindBuilder::aStaticBind($list_field)->build()->getField()];
        yield 'user group list' => [ListUserGroupBindBuilder::aUserGroupBind($list_field)->build()->getField()];
    }

    /**
     * @dataProvider generateFieldTypes
     */
    public function testItRejectsInvalidComparisonToMyself(\Tracker_FormElement_Field $field): void
    {
        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')->willReturn($field);

        $this->comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new CurrentUserValueWrapper(ProvideCurrentUserStub::buildWithUser($this->user))
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
        yield 'Tail of AndOperand' => [
            new AndExpression(
                $valid_comparison,
                new AndOperand($valid_comparison, new AndOperand($invalid_comparison))
            ),
        ];
        yield 'OrExpression' => [new OrExpression(new AndExpression($invalid_comparison))];
        yield 'OrOperand' => [
            new OrExpression(
                new AndExpression($valid_comparison),
                new OrOperand(new AndExpression($invalid_comparison))
            ),
        ];
        yield 'Tail of OrOperand' => [
            new OrExpression(
                new AndExpression($valid_comparison),
                new OrOperand(
                    new AndExpression($valid_comparison),
                    new OrOperand(new AndExpression($invalid_comparison))
                )
            ),
        ];
        yield 'Parenthesis' => [
            new AndExpression(
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
        $int_field = IntFieldBuilder::anIntField(102)->build();

        $this->formelement_factory->method('getUsedFormElementFieldByNameForUser')
            ->willReturnOnConsecutiveCalls($int_field, $int_field, $int_field);
        $this->parsed_query = $parsed_query;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }
}
