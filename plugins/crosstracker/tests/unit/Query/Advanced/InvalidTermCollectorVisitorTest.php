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

namespace Tuleap\CrossTracker\Query\Advanced;

use EventManager;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\ArtifactIdMetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\AssignedToChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\InvalidMetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\StatusChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\SubmissionDateChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\TextSemanticChecker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\SystemTypePresenterBuilder;
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Searchable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink\ArtifactLinkTypeChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractorForOpenList;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Semantic\Contributor\ContributorFieldRetriever;
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributorFactory;
use Tuleap\Tracker\Test\Builders\Fields\CheckboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FilesFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\MultiSelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\RadioButtonFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticStatusFieldStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvalidTermCollectorVisitorTest extends TestCase
{
    use LegacyTabTranslationsSupport;

    private const string FIELD_NAME        = 'a_field';
    private const string CURRENT_USER_NAME = 'alice';
    private InvalidSearchablesCollection $invalid_searchable_collection;
    private Comparison $comparison;
    private ?Logical $parsed_query;
    private \PFUser $user;
    private \Tuleap\Tracker\Tracker $first_tracker;
    private \Tuleap\Tracker\Tracker $second_tracker;
    private RetrieveUsedFieldsStub $fields_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(67)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(21)->build();
        $this->user           = UserTestBuilder::aUser()
            ->withUserName(self::CURRENT_USER_NAME)
            ->withId(443)
            ->build();

        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntegerFieldBuilder::anIntField(628)
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
        $user_manager   = $this->createStub(\UserManager::class);
        $user_retriever = ProvideAndRetrieveUserStub::build($this->user);

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
                new MetadataChecker(
                    new InvalidMetadataChecker(
                        new TextSemanticChecker(),
                        new StatusChecker(),
                        new AssignedToChecker($user_retriever),
                        new QueryValidation\Metadata\ArtifactSubmitterChecker(
                            $user_retriever
                        ),
                        new SubmissionDateChecker(),
                        new ArtifactIdMetadataChecker(),
                    ),
                    new InvalidOrderByListChecker(
                        RetrieveSemanticStatusFieldStub::build(),
                        new ContributorFieldRetriever(TrackerSemanticContributorFactory::instance()),
                    ),
                ),
                new DuckTypedFieldChecker(
                    $this->fields_retriever,
                    RetrieveFieldTypeStub::withDetectionOfType(),
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
                                $open_list_value_dao,
                                $list_field_bind_value_normalizer,
                            ),
                            $ugroup_label_converter
                        ),
                        new ArtifactSubmitterChecker($user_manager),
                        true,
                    ),
                    new ReadableFieldRetriever(
                        $this->fields_retriever,
                        RetrieveUserPermissionOnFieldsStub::build(),
                    )
                )
            ),
            new ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    $this->createStub(TypeDao::class),
                    $this->createStub(ArtifactLinksUsageDao::class),
                    new SystemTypePresenterBuilder(EventManager::instance())
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
            IntegerFieldBuilder::anIntField(628)
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
        Searchable $searchable,
        ValueWrapper $valid_value,
    ): iterable {
        $open = new StatusOpenValueWrapper();
        yield '= OPEN()' => [new EqualComparison($searchable, $open)];
        yield 'IN()' => [new InComparison($searchable, new InValueWrapper([$valid_value]))];
        yield 'NOT IN()' => [new NotInComparison($searchable, new InValueWrapper([$valid_value]))];
    }

    private static function generateInvalidComparisonsToEmptyString(Searchable $searchable, ValueWrapper $valid_value): iterable
    {
        $empty_value = new SimpleValueWrapper('');
        yield '< empty string' => [new LesserThanComparison($searchable, $empty_value)];
        yield '<= empty string' => [new LesserThanOrEqualComparison($searchable, $empty_value)];
        yield '> empty string' => [new GreaterThanComparison($searchable, $empty_value)];
        yield '>= empty string' => [new GreaterThanOrEqualComparison($searchable, $empty_value)];
        yield "BETWEEN('', valid value)" => [
            new BetweenComparison($searchable, new BetweenValueWrapper($empty_value, $valid_value)),
        ];
        yield "BETWEEN(valid value, '')" => [
            new BetweenComparison($searchable, new BetweenValueWrapper($valid_value, $empty_value)),
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

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidNumericComparisons')]
    public function testItRejectsInvalidNumericComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntegerFieldBuilder::anIntField(975)
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

    private static function generateInvalidComparisonsForFieldsThatAreNotNumeric(Searchable $searchable, SimpleValueWrapper $valid_value): iterable
    {
        yield '< anything' => [new LesserThanComparison($searchable, $valid_value)];
        yield '<= anything' => [new LesserThanOrEqualComparison($searchable, $valid_value)];
        yield '> anything' => [new GreaterThanComparison($searchable, $valid_value)];
        yield '>= anything' => [new GreaterThanOrEqualComparison($searchable, $valid_value)];
        yield 'BETWEEN anything' => [
            new BetweenComparison($searchable, new BetweenValueWrapper($valid_value, $valid_value)),
        ];
    }

    private static function generateInvalidTextComparisons(Searchable $searchable): iterable
    {
        $valid_value = new SimpleValueWrapper('Graphium');
        $now         = new CurrentDateTimeValueWrapper(null, null);
        yield '= NOW()' => [new EqualComparison($searchable, $now)];
        yield from self::generateInvalidComparisonsForFieldsThatAreNotNumeric($searchable, $valid_value);
        yield from self::generateInvalidComparisonsForFieldsThatAreNotLists($searchable, $valid_value);
    }

    public static function generateInvalidTextFieldComparisons(): iterable
    {
        yield from self::generateInvalidTextComparisons(new Field(self::FIELD_NAME));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidTextFieldComparisons')]
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

    private static function generateInvalidDateComparisons(Searchable $searchable): iterable
    {
        $valid_value = new SimpleValueWrapper('2024-02-22');
        yield '= string value' => [new EqualComparison($searchable, new SimpleValueWrapper('string'))];
        yield from self::generateInvalidComparisonsToEmptyString($searchable, $valid_value);
        yield from self::generateInvalidComparisonsForFieldsThatAreNotLists($searchable, $valid_value);
    }

    public static function generateInvalidDateFieldComparisons(): iterable
    {
        yield from self::generateInvalidDateComparisons(new Field(self::FIELD_NAME));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidDateFieldComparisons')]
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

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidTextFieldComparisons')]
    public function testItRejectsInvalidFileComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            FilesFieldBuilder::aFileField(324)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );
        $this->comparison       = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    private static function generateInvalidListComparisonsToEmptyString(Searchable $searchable, ValueWrapper $valid_value): iterable
    {
        $empty_value = new SimpleValueWrapper('');
        yield "IN('', valid value)" => [
            new InComparison($searchable, new InValueWrapper([$empty_value, $valid_value])),
        ];
        yield "IN(valid value, '')" => [
            new InComparison($searchable, new InValueWrapper([$valid_value, $empty_value])),
        ];
        yield "NOT IN('', valid value)" => [
            new NotInComparison($searchable, new InValueWrapper([$empty_value, $valid_value])),
        ];
        yield "NOT IN(valid value, '')" => [
            new NotInComparison($searchable, new InValueWrapper([$valid_value, $empty_value])),
        ];
    }

    private static function generateInvalidListComparisons(
        Searchable $searchable,
        SimpleValueWrapper $valid_value,
    ): iterable {
        $now = new CurrentDateTimeValueWrapper(null, null);
        yield '= NOW()' => [new EqualComparison($searchable, $now)];
        yield from self::generateInvalidComparisonsForFieldsThatAreNotNumeric($searchable, $valid_value);
        yield from self::generateInvalidListComparisonsToEmptyString($searchable, $valid_value);
    }

    public static function generateInvalidListFieldComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $valid_value = new SimpleValueWrapper('unbait');
        $open        = new StatusOpenValueWrapper();
        yield '= OPEN()' => [new EqualComparison($field, $open)];
        yield from self::generateInvalidListComparisons($field, $valid_value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidListFieldComparisons')]
    public function testItRejectsInvalidListComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                SelectboxFieldBuilder::aSelectboxField(334)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                MultiSelectboxFieldBuilder::aMultiSelectboxField(789)
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

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidListFieldComparisons')]
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

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidListFieldComparisons')]
    public function testItRejectsMoreInvalidOpenListComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                OpenListFieldBuilder::anOpenListField()
                    ->withName(self::FIELD_NAME)
                    ->withTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues(['unbait'])->build()->getField(),
        );
        $this->comparison       = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateFieldsThatCannotBeComparedToMyself(): iterable
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(311)->build();
        $user    = UserTestBuilder::buildWithId(300);
        yield 'int' => [
            IntegerFieldBuilder::anIntField(132)
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
            FilesFieldBuilder::aFileField(415)
                ->withName(self::FIELD_NAME)
                ->inTracker($tracker)
                ->withReadPermission($user, true)
                ->build(),
            $tracker,
            $user,
        ];

        $list_field = SelectboxFieldBuilder::aSelectboxField(637)
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

        $open_list = OpenListFieldBuilder::anOpenListField()
            ->withName(self::FIELD_NAME)
            ->withTracker($tracker)
            ->withReadPermission($user, true)
            ->build();
        yield 'static open list' => [
            ListStaticBindBuilder::aStaticBind($open_list)->build()->getField(),
            $tracker,
            $user,
        ];
        yield 'user group open list' => [
            ListUserGroupBindBuilder::aUserGroupBind($open_list)->build()->getField(),
            $tracker,
            $user,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateFieldsThatCannotBeComparedToMyself')]
    public function testItRejectsInvalidFieldComparisonsToMyself(
        \Tuleap\Tracker\FormElement\Field\TrackerField $field,
        \Tuleap\Tracker\Tracker $tracker,
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

    public static function generateInvalidTitleComparisons(): iterable
    {
        yield from self::generateInvalidTextComparisons(new Metadata('title'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidTitleComparisons')]
    public function testItRejectsInvalidTitleComparisons(Comparison $comparison): void
    {
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidDescriptionComparisons(): iterable
    {
        yield from self::generateInvalidTextComparisons(new Metadata('description'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidDescriptionComparisons')]
    public function testItRejectsInvalidDescriptionComparisons(Comparison $comparison): void
    {
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidStatusComparisons(): iterable
    {
        $semantic     = new Metadata('status');
        $simple_value = new SimpleValueWrapper('ongoing');
        $empty_value  = new SimpleValueWrapper('');
        yield '= simple value' => [new EqualComparison($semantic, $simple_value)];
        yield '!= simple value' => [new NotEqualComparison($semantic, $simple_value)];
        yield '= empty string' => [new EqualComparison($semantic, $empty_value)];
        yield '!= empty string' => [new NotEqualComparison($semantic, $empty_value)];
        yield 'IN(simple value)' => [new InComparison($semantic, new InValueWrapper([$simple_value]))];
        yield 'IN(OPEN())' => [new InComparison($semantic, new InValueWrapper([new StatusOpenValueWrapper()]))];
        yield 'NOT IN(simple value)' => [new NotInComparison($semantic, new InValueWrapper([$simple_value]))];
        yield 'NOT IN(OPEN())' => [new NotInComparison($semantic, new InValueWrapper([new StatusOpenValueWrapper()]))];
        yield from self::generateInvalidListComparisons($semantic, $simple_value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidStatusComparisons')]
    public function testItRejectsInvalidStatusComparisons(Comparison $comparison): void
    {
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidAssignedToComparisons(): iterable
    {
        $semantic    = new Metadata('assigned_to');
        $valid_value = new SimpleValueWrapper(self::CURRENT_USER_NAME);
        $open        = new StatusOpenValueWrapper();
        yield '= OPEN()' => [new EqualComparison($semantic, $open)];
        yield from self::generateInvalidListComparisons($semantic, $valid_value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidAssignedToComparisons')]
    public function testItRejectsInvalidAssignedToComparisons(Comparison $comparison): void
    {
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidSubmittedOnComparisons(): iterable
    {
        $always_there_field = new Metadata('submitted_on');
        $now                = new CurrentDateTimeValueWrapper(null, null);
        yield '= NOW()' => [new EqualComparison($always_there_field, $now)];
        yield from self::generateInvalidDateComparisons($always_there_field);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidSubmittedOnComparisons')]
    public function testItRejectsInvalidSubmittedOnComparisons(Comparison $comparison): void
    {
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidLastUpdateDateComparison(): iterable
    {
        $always_there_field = new Metadata('last_update_date');
        $now                = new CurrentDateTimeValueWrapper(null, null);
        yield '= NOW()' => [new EqualComparison($always_there_field, $now)];
        yield from self::generateInvalidDateComparisons($always_there_field);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidLastUpdateDateComparison')]
    public function testItRejectsInvalidLastUpdateDateComparisons(Comparison $comparison): void
    {
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidSubmittedByComparisons(): iterable
    {
        $always_there_field = new Metadata('submitted_by');
        $valid_value        = new SimpleValueWrapper(self::CURRENT_USER_NAME);
        $open               = new StatusOpenValueWrapper();
        yield '= OPEN()' => [new EqualComparison($always_there_field, $open)];
        yield from self::generateInvalidListComparisons($always_there_field, $valid_value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidSubmittedByComparisons')]
    public function testItRejectsInvalidSubmittedByComparisons(Comparison $comparison): void
    {
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateInvalidLastUpdateByComparisons(): iterable
    {
        $always_there_field = new Metadata('last_update_by');
        $valid_value        = new SimpleValueWrapper(self::CURRENT_USER_NAME);
        $open               = new StatusOpenValueWrapper();
        yield '= OPEN()' => [new EqualComparison($always_there_field, $open)];
        yield from self::generateInvalidListComparisons($always_there_field, $valid_value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateInvalidLastUpdateByComparisons')]
    public function testItRejectsInvalidLastUpdateByComparisons(Comparison $comparison): void
    {
        $this->comparison = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateMetadataThatCannotBeComparedToMyself(): iterable
    {
        yield '@title' => [new Metadata('title')];
        yield '@description' => [new Metadata('description')];
        yield '@status' => [new Metadata('status')];
        yield '@submitted_on' => [new Metadata('submitted_on')];
        yield '@last_update_date' => [new Metadata('last_update_date')];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateMetadataThatCannotBeComparedToMyself')]
    public function testItRejectsInvalidMetadataComparisonsToMyself(Metadata $metadata): void
    {
        $this->comparison = new EqualComparison(
            $metadata,
            new CurrentUserValueWrapper(ProvideCurrentUserStub::buildWithUser($this->user))
        );

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    private static function generateNestedExpressions(
        Comparison $valid_comparison,
        Comparison $invalid_comparison,
    ): iterable {
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

    public static function generateNestedFields(): iterable
    {
        $valid_comparison   = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper(5));
        $invalid_comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper('string value')
        );
        yield from self::generateNestedExpressions($valid_comparison, $invalid_comparison);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateNestedFields')]
    public function testItAddsInvalidFieldInNestedExpressions(Logical $parsed_query): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntegerFieldBuilder::anIntField(893)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            IntegerFieldBuilder::anIntField(120)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );
        $this->parsed_query     = $parsed_query;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public static function generateNestedMetadata(): iterable
    {
        $valid_comparison   = new EqualComparison(new Metadata('title'), new SimpleValueWrapper('advantage'));
        $invalid_comparison = new EqualComparison(new Metadata('status'), new SimpleValueWrapper('simple value'));
        yield from self::generateNestedExpressions($valid_comparison, $invalid_comparison);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateNestedMetadata')]
    public function testItAddsInvalidMetadataInNestedExpressions(Logical $parsed_query): void
    {
        $this->parsed_query = $parsed_query;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }
}
