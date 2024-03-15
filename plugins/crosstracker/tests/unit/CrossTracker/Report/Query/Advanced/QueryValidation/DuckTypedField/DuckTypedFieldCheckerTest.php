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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\DuckTypedField;

use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldNotFoundInAnyTrackerFault;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypesAreIncompatibleFault;
use Tuleap\CrossTracker\Tests\Builders\InvalidSearchableCollectorParametersBuilder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FlatInvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractorForOpenList;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class DuckTypedFieldCheckerTest extends TestCase
{
    use LegacyTabTranslationsSupport;

    private const FIELD_NAME = 'toto';
    private \Tracker $first_tracker;
    private \Tracker $second_tracker;
    private \PFUser $user;
    private RetrieveUsedFieldsStub $fields_retriever;

    protected function setUp(): void
    {
        $this->first_tracker    = TrackerTestBuilder::aTracker()->withId(86)->build();
        $this->second_tracker   = TrackerTestBuilder::aTracker()->withId(94)->build();
        $this->user             = UserTestBuilder::buildWithId(103);
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntFieldBuilder::anIntField(841)
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

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function check(Comparison $comparison): Ok | Err
    {
        $visitor_parameters = InvalidSearchableCollectorParametersBuilder::aParameter()
            ->withUser($this->user)
            ->onTrackers($this->first_tracker, $this->second_tracker)
            ->withComparison($comparison)
            ->build();

        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new \BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );

        $checker = new DuckTypedFieldChecker(
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
                        new OpenListValueDao(),
                        $list_field_bind_value_normalizer,
                    ),
                    $ugroup_label_converter
                ),
                new ArtifactSubmitterChecker(\UserManager::instance()),
                true,
            )
        );
        return $checker->checkFieldIsValid(
            new Field(self::FIELD_NAME),
            $visitor_parameters
        );
    }

    public function testCheckWhenAllFieldsAreIntOrFloat(): void
    {
        self::assertTrue(Result::isOk($this->check(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper(12)
        ))));
    }

    public function testCheckFailsWhenFieldsAreIncompatible(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntFieldBuilder::anIntField(308)
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

        $result = $this->check(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper(12)
        ));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypesAreIncompatibleFault::class, $result->error);
    }

    public function testCheckFailsWhenFirstFieldIsNotSupported(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ExternalFieldBuilder::anExternalField(569)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            IntFieldBuilder::anIntField(308)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );

        $result = $this->check(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper(12)
        ));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(Fault::class, $result->error);
    }

    public function testCheckFailsWhenUserCannotReadFieldInAnyTracker(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntFieldBuilder::anIntField(841)
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

        $result = $this->check(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper(12)
        ));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldNotFoundInAnyTrackerFault::class, $result->error);
    }

    public function testCheckGoodWhenLabelMissingOnlyInOneField(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(586)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                0 => 'a',
                1 => 'b',
            ])->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(489)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                2 => 'c',
                3 => 'd',
            ])->build()->getField()
        );

        $result = $this->check(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper('a')
        ));
        self::assertTrue(Result::isOk($result));
    }

    public function testCheckFailsWhenLabelMissingInAllField(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(586)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                0 => 'a',
                1 => 'b',
            ])->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(489)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->withStaticValues([
                2 => 'c',
                3 => 'd',
            ])->build()->getField()
        );

        $result = $this->check(new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper('e')
        ));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(Fault::class, $result->error);
        self::assertEquals("The value 'e' doesn't exist for the list field '" . self::FIELD_NAME . "'.", (string) $result->error);
    }
}
