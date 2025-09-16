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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation\DuckTypedField;

use BaseLanguageFactory;
use PFUser;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldLinkedToMetadataFault;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldNotFoundInAnyTrackerFault;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypesAreIncompatibleFault;
use Tuleap\CrossTracker\Query\Advanced\InvalidOrderByBuilderParameters;
use Tuleap\CrossTracker\Query\Advanced\InvalidSelectableCollectorParameters;
use Tuleap\CrossTracker\Query\Advanced\ReadableFieldRetriever;
use Tuleap\CrossTracker\Tests\Builders\InvalidSearchableCollectorParametersBuilder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
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
use Tuleap\Tracker\Report\Query\Advanced\InvalidSelectablesCollection;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedByFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedOnFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Tracker;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DuckTypedFieldCheckerTest extends TestCase
{
    use LegacyTabTranslationsSupport;

    private const string FIELD_NAME = 'toto';
    private Tracker $first_tracker;
    private Tracker $second_tracker;
    private PFUser $user;
    private RetrieveUsedFieldsStub $fields_retriever;
    private RetrieveUserPermissionOnFieldsStub $user_permission_on_fields;

    #[\Override]
    protected function setUp(): void
    {
        $this->first_tracker             = TrackerTestBuilder::aTracker()->withId(86)->build();
        $this->second_tracker            = TrackerTestBuilder::aTracker()->withId(94)->build();
        $this->user                      = UserTestBuilder::buildWithId(103);
        $this->user_permission_on_fields = RetrieveUserPermissionOnFieldsStub::build();
        $this->fields_retriever          = RetrieveUsedFieldsStub::withFields(
            IntegerFieldBuilder::anIntField(841)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            FloatFieldBuilder::aFloatField(805)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );
    }

    private function buildChecker(): DuckTypedFieldChecker
    {
        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );

        return new DuckTypedFieldChecker(
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
                        new OpenListValueDao(),
                        $list_field_bind_value_normalizer,
                    ),
                    $ugroup_label_converter
                ),
                new ArtifactSubmitterChecker(UserManager::instance()),
                true,
            ),
            new ReadableFieldRetriever(
                $this->fields_retriever,
                $this->user_permission_on_fields,
            ),
        );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function checkForSearch(Comparison $comparison): Ok|Err
    {
        $visitor_parameters = InvalidSearchableCollectorParametersBuilder::aParameter()
            ->withUser($this->user)
            ->onTrackers($this->first_tracker, $this->second_tracker)
            ->withComparison($comparison)
            ->build();
        return $this->buildChecker()->checkFieldIsValidForSearch(
            new Field(self::FIELD_NAME),
            $visitor_parameters
        );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function checkForSelect(): Ok|Err
    {
        return $this->buildChecker()->checkFieldIsValidForSelect(
            new Field(self::FIELD_NAME),
            new InvalidSelectableCollectorParameters(
                new InvalidSelectablesCollection(),
                [$this->first_tracker, $this->second_tracker],
                $this->user,
            ),
        );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function checkForOrderBy(): Ok|Err
    {
        return $this->buildChecker()->checkFieldIsValidForOrderBy(
            new Field(self::FIELD_NAME),
            new InvalidOrderByBuilderParameters(
                [$this->first_tracker, $this->second_tracker],
                $this->user,
            ),
        );
    }

    public function testSearchCheckWhenAllFieldsAreIntOrFloat(): void
    {
        self::assertTrue(Result::isOk($this->checkForSearch(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper(12)
        ))));
    }

    public function testSearchCheckFailsWhenFieldsAreIncompatible(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntegerFieldBuilder::anIntField(308)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            StringFieldBuilder::aStringField(358)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $result = $this->checkForSearch(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper(12)
        ));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypesAreIncompatibleFault::class, $result->error);
    }

    public function testSearchCheckFailsWhenFirstFieldIsNotSupported(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ExternalFieldBuilder::anExternalField(569)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            IntegerFieldBuilder::anIntField(308)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );

        $result = $this->checkForSearch(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper(12)
        ));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(Fault::class, $result->error);
    }

    public function testSearchCheckFailsWhenUserCannotReadFieldInAnyTracker(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntegerFieldBuilder::anIntField(841)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, false)
                ->build(),
            FloatFieldBuilder::aFloatField(805)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, false)
                ->build()
        );

        $result = $this->checkForSearch(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper(12)
        ));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldNotFoundInAnyTrackerFault::class, $result->error);
    }

    public function testSearchCheckFailsWhenTheFieldIsAlreadyRelatedToAnAlwaysThereField(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            SubmittedByFieldBuilder::aSubmittedByField(156) // @submitted_on
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );
        $this->user_permission_on_fields->withPermissionOn([156], FieldPermissionType::PERMISSION_READ);

        $result = $this->checkForSearch(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper(12)
        ));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldLinkedToMetadataFault::class, $result->error);
    }

    public function testSearchCheckGoodWhenLabelMissingOnlyInOneField(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                SelectboxFieldBuilder::aSelectboxField(586)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                0 => 'a',
                1 => 'b',
            ])->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                SelectboxFieldBuilder::aSelectboxField(489)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                2 => 'c',
                3 => 'd',
            ])->build()->getField()
        );

        $result = $this->checkForSearch(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper('a')
        ));
        self::assertTrue(Result::isOk($result));
    }

    public function testSearchCheckFailsWhenLabelMissingInAllField(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                SelectboxFieldBuilder::aSelectboxField(586)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                0 => 'a',
                1 => 'b',
            ])->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                SelectboxFieldBuilder::aSelectboxField(489)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                2 => 'c',
                3 => 'd',
            ])->build()->getField()
        );

        $result = $this->checkForSearch(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper('e')
        ));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(Fault::class, $result->error);
        self::assertEquals("The value 'e' doesn't exist for the list field '" . self::FIELD_NAME . "'.", (string) $result->error);
    }

    public function testSearchCheckGoodWhenStrictUnionOfLabels(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                SelectboxFieldBuilder::aSelectboxField(586)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                0 => 'Todo',
            ])->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                SelectboxFieldBuilder::aSelectboxField(489)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                2 => 'To do',
            ])->build()->getField()
        );

        $result = $this->checkForSearch(new InComparison(
            new Field(self::FIELD_NAME),
            new InValueWrapper([
                new SimpleValueWrapper('Todo'),
                new SimpleValueWrapper('To do'),
            ])
        ));
        self::assertTrue(Result::isOk($result));
    }

    public function testSelectCheckFailsWhenTheFieldIsAlreadyRelatedToAnAlwaysThereField(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            SubmittedOnFieldBuilder::aSubmittedOnField(156) // @submitted_on
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->build(),
        );
        $this->user_permission_on_fields->withPermissionOn([156], FieldPermissionType::PERMISSION_READ);

        $result = $this->checkForSelect();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldLinkedToMetadataFault::class, $result->error);
    }

    public function testSelectCheckAcceptDateAndDateTime(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            DateFieldBuilder::aDateField(156)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->build(),
            DateFieldBuilder::aDateField(189)
                ->withTime()
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->build(),
        );
        $this->user_permission_on_fields->withPermissionOn([156], FieldPermissionType::PERMISSION_READ);
        $this->user_permission_on_fields->withPermissionOn([189], FieldPermissionType::PERMISSION_UPDATE);

        self::assertTrue(Result::isOk($this->checkForSelect()));
    }

    public function testOrderByCheckFailsWhenTheFieldIsAlreadyRelatedToAnAlwaysThereField(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            SubmittedByFieldBuilder::aSubmittedByField(156) // @submitted_on
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->build(),
        );
        $this->user_permission_on_fields->withPermissionOn([156], FieldPermissionType::PERMISSION_READ);

        $result = $this->checkForOrderBy();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldLinkedToMetadataFault::class, $result->error);
    }
}
