<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Between\BetweenComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Equal\EqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\In\InComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ListValueValidator;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotEqual\NotEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotIn\NotInComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Tests\Stub\MetadataCheckerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parenthesis;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink\ArtifactLinkTypeChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FlatInvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractorForOpenList;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Test\Builders\Fields\CheckboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\RadioButtonFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class InvalidTermCollectorVisitorTest extends TestCase
{
    use LegacyTabTranslationsSupport;

    private const FIELD_NAME = 'a_field';
    private MetadataCheckerStub $metadata_checker;
    private InvalidSearchablesCollection $invalid_searchable_collection;
    private Comparison $comparison;
    private ?Logical $parsed_query;
    private \PFUser $user;
    private \Tracker $first_tracker;
    private \Tracker $second_tracker;
    private RetrieveUsedFieldsStub $fields_retriever;

    protected function setUp(): void
    {
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(67)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(21)->build();
        $this->user           = UserTestBuilder::buildWithId(443);

        $this->metadata_checker = MetadataCheckerStub::withValidMetadata();
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntFieldBuilder::anIntField(628)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            FloatFieldBuilder::aFloatField(274)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $this->comparison   = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper(12));
        $this->parsed_query = null;

        $this->invalid_searchable_collection = new InvalidSearchablesCollection();
    }

    private function check(): void
    {
        $user_manager = $this->createStub(\UserManager::class);

        $date_validator                 = new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME);
        $list_value_validator           = new ListValueValidator(new EmptyStringAllowed(), $user_manager);
        $list_value_validator_not_empty = new ListValueValidator(new EmptyStringForbidden(), $user_manager);

        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new \BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );
        $open_list_value_dao              = $this->createMock(OpenListValueDao::class);
        $open_list_value_dao->method('searchByFieldId')->willReturn(\TestHelper::emptyDar());

        $collector = new InvalidTermCollectorVisitor(
            new InvalidSearchableCollectorVisitor(
                $this->metadata_checker,
                new DuckTypedFieldChecker(
                    $this->fields_retriever,
                    RetrieveFieldTypeStub::withDetectionOfType(),
                    new FlatInvalidFieldChecker(
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
                                $open_list_value_dao,
                                $list_field_bind_value_normalizer,
                            ),
                            $ugroup_label_converter
                        ),
                        new ArtifactSubmitterChecker($user_manager),
                        true,
                    ),
                )
            ),
            new EqualComparisonChecker($date_validator, $list_value_validator),
            new NotEqualComparisonChecker($date_validator, $list_value_validator),
            new GreaterThanComparisonChecker($date_validator, $list_value_validator),
            new GreaterThanOrEqualComparisonChecker($date_validator, $list_value_validator),
            new LesserThanComparisonChecker($date_validator, $list_value_validator),
            new LesserThanOrEqualComparisonChecker($date_validator, $list_value_validator),
            new BetweenComparisonChecker($date_validator, $list_value_validator),
            new InComparisonChecker($date_validator, $list_value_validator_not_empty),
            new NotInComparisonChecker($date_validator, $list_value_validator_not_empty),
            new ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    $this->createStub(TypeDao::class),
                    $this->createStub(ArtifactLinksUsageDao::class)
                )
            )
        );
        $collector->collectErrors(
            $this->parsed_query ?? new AndExpression($this->comparison),
            $this->invalid_searchable_collection,
            [$this->first_tracker, $this->second_tracker],
            $this->user
        );
    }

    public function testItAddsNotSupportedFieldToInvalidCollection(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ExternalFieldBuilder::anExternalField(900)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItAddsFieldNotFoundToInvalidCollection(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withNoFields();

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
    }

    public function testItAddsFieldUserCanNotReadToInvalidCollection(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntFieldBuilder::anIntField(628)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, false)
                ->build()
        );

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
    }

    public function testItChecksFieldIsValid(): void
    {
        $this->check();
        self::assertFalse($this->invalid_searchable_collection->hasInvalidSearchable());
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

    private static function generateInvalidComparisonsToEmptyString(Field $field, ValueWrapper $valid_value): iterable
    {
        $empty_value = new SimpleValueWrapper('');
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
    }

    public static function generateInvalidNumericComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper(10.5);
        $now         = new CurrentDateTimeValueWrapper(null, null);
        yield '= string value' => [new EqualComparison($field, new SimpleValueWrapper('string'))];
        yield '= NOW()' => [new EqualComparison($field, $now)];
        yield from self::generateInvalidComparisonsToEmptyString($field, $valid_value);
        yield from self::generateInvalidComparisonsForFieldsThatAreNotLists($field, $valid_value);
    }

    /**
     * @dataProvider generateInvalidNumericComparisons
     */
    public function testItRejectsInvalidNumericComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntFieldBuilder::anIntField(975)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            FloatFieldBuilder::aFloatField(659)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );
        $this->comparison       = $comparison;

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
        yield from self::generateInvalidComparisonsForFieldsThatAreNotLists($field, $valid_value);
    }

    /**
     * @dataProvider generateInvalidTextComparisons
     */
    public function testItRejectsInvalidTextComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            StringFieldBuilder::aStringField(619)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TextFieldBuilder::aTextField(204)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );
        $this->comparison       = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidDateComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper('2024-02-22');
        yield '= string value' => [new EqualComparison($field, new SimpleValueWrapper('string'))];
        yield from self::generateInvalidComparisonsToEmptyString($field, $valid_value);
        yield from self::generateInvalidComparisonsForFieldsThatAreNotLists($field, $valid_value);
    }

    /**
     * @dataProvider generateInvalidDateComparisons
     */
    public function testItRejectsInvalidDateComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            DateFieldBuilder::aDateField(130)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );
        $this->comparison       = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItRejectsDateFieldWithoutTimeComparedToDateTime(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            DateFieldBuilder::aDateField(130)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );
        $this->comparison       = new EqualComparison(
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
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            FileFieldBuilder::aFileField(324)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );
        $this->comparison       = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidListComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper('unbait');
        $empty_value = new SimpleValueWrapper('');
        $open        = new StatusOpenValueWrapper();
        $now         = new CurrentDateTimeValueWrapper(null, null);

        yield '< anything' => [new LesserThanComparison($field, $valid_value)];
        yield '<= anything' => [new LesserThanOrEqualComparison($field, $valid_value)];
        yield '> anything' => [new GreaterThanComparison($field, $valid_value)];
        yield '>= anything' => [new GreaterThanOrEqualComparison($field, $valid_value)];
        yield 'BETWEEN anything' => [
            new BetweenComparison($field, new BetweenValueWrapper($valid_value, $valid_value)),
        ];
        yield '= NOW()' => [new EqualComparison($field, $now)];
        yield '= OPEN()' => [new EqualComparison($field, $open)];
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

    /**
     * @dataProvider generateInvalidListComparisons
     */
    public function testItRejectsInvalidListComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(334)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(789)
                    ->withName(self::FIELD_NAME)
                    ->withMultipleValues()
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField()
        );
        $this->comparison       = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidListComparisons
     */
    public function testItRejectsMoreInvalidListComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                CheckboxFieldBuilder::aCheckboxField(167)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                RadioButtonFieldBuilder::aRadioButtonField(930)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField()
        );
        $this->comparison       = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    /**
     * @dataProvider generateInvalidListComparisons
     */
    public function testItRejectsMoreInvalidOpenListComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            OpenListFieldBuilder::aBind()
                ->withName(self::FIELD_NAME)
                ->withTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->withStaticValues(['unbait'])
                ->buildStaticBind()->getField(),
        );
        $this->comparison       = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateFieldTypes(): iterable
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(311)->build();
        $user    = UserTestBuilder::buildWithId(300);
        yield 'int' => [
            IntFieldBuilder::anIntField(132)
                ->withName(self::FIELD_NAME)
                ->inTracker($tracker)
                ->withReadPermission($user, true)
                ->build(),
            $tracker,
            $user,
        ];
        yield 'float' => [
            FloatFieldBuilder::aFloatField(202)
                ->withName(self::FIELD_NAME)
                ->inTracker($tracker)
                ->withReadPermission($user, true)
                ->build(),
            $tracker,
            $user,
        ];
        yield 'string' => [
            StringFieldBuilder::aStringField(716)
                ->withName(self::FIELD_NAME)
                ->inTracker($tracker)
                ->withReadPermission($user, true)
                ->build(),
            $tracker,
            $user,
        ];
        yield 'text' => [
            TextFieldBuilder::aTextField(198)
                ->withName(self::FIELD_NAME)
                ->inTracker($tracker)
                ->withReadPermission($user, true)
                ->build(),
            $tracker,
            $user,
        ];
        yield 'date' => [
            DateFieldBuilder::aDateField(514)
                ->withName(self::FIELD_NAME)
                ->inTracker($tracker)
                ->withReadPermission($user, true)
                ->build(),
            $tracker,
            $user,
        ];
        yield 'file' => [
            FileFieldBuilder::aFileField(415)
                ->withName(self::FIELD_NAME)
                ->inTracker($tracker)
                ->withReadPermission($user, true)
                ->build(),
            $tracker,
            $user,
        ];

        $list_field = ListFieldBuilder::aListField(637)
            ->withName(self::FIELD_NAME)
            ->inTracker($tracker)
            ->withReadPermission($user, true)
            ->build();

        yield 'static list' => [
            ListStaticBindBuilder::aStaticBind($list_field)->build()->getField(),
            $tracker,
            $user,
        ];
        yield 'user group list' => [
            ListUserGroupBindBuilder::aUserGroupBind($list_field)->build()->getField(),
            $tracker,
            $user,
        ];

        $open_list_field_builder = OpenListFieldBuilder::aBind()
            ->withName(self::FIELD_NAME)
            ->withTracker($tracker)
            ->withReadPermission($user, true);
        yield 'static open list' => [
            $open_list_field_builder->buildStaticBind()->getField(),
            $tracker,
            $user,
        ];
        yield 'user group open list' => [
            $open_list_field_builder->buildUserGroupBind()->getField(),
            $tracker,
            $user,
        ];
    }

    /**
     * @dataProvider generateFieldTypes
     */
    public function testItRejectsInvalidComparisonsToMyself(
        \Tracker_FormElement_Field $field,
        \Tracker $tracker,
        \PFUser $user,
    ): void {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields($field);
        $this->first_tracker    = $tracker;
        $this->user             = $user;

        $this->comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new CurrentUserValueWrapper(ProvideCurrentUserStub::buildWithUser($this->user))
        );

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItAddsUnknownMetadataToInvalidCollection(): void
    {
        $this->comparison = new EqualComparison(new Metadata('unknown'), new SimpleValueWrapper(12));

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
    }

    public function testItAllowsValidMetadata(): void
    {
        $this->comparison = new EqualComparison(new Metadata("title"), new SimpleValueWrapper('romeo'));

        $this->check();
        self::assertFalse($this->invalid_searchable_collection->hasInvalidSearchable());
    }

    public function testItAddsInvalidMetadataToCollection(): void
    {
        $this->comparison       = new EqualComparison(new Metadata("title"), new SimpleValueWrapper('romeo'));
        $this->metadata_checker = MetadataCheckerStub::withInvalidMetadata();

        $this->check();
        self::assertTrue($this->invalid_searchable_collection->hasInvalidSearchable());
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
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntFieldBuilder::anIntField(893)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            IntFieldBuilder::anIntField(120)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );
        $this->parsed_query     = $parsed_query;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }
}
