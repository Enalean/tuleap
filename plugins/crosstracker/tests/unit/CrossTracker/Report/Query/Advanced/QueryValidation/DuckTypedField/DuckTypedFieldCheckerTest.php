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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FlatInvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
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
    private function check(): Ok|Err
    {
        $visitor_parameters = InvalidSearchableCollectorParametersBuilder::aParameter()
            ->withUser($this->user)
            ->onTrackers($this->first_tracker, $this->second_tracker)
            ->build();

        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new \BaseLanguageFactory()
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
                    new CollectionOfNormalizedBindLabelsExtractor(
                        $list_field_bind_value_normalizer,
                        $ugroup_label_converter
                    ),
                    $ugroup_label_converter
                ),
                new ArtifactSubmitterChecker(\UserManager::instance())
            )
        );
        return $checker->checkFieldIsValid(
            new Field(self::FIELD_NAME),
            $visitor_parameters
        );
    }

    public function testCheckWhenAllFieldsAreIntOrFloat(): void
    {
        self::assertTrue(Result::isOk($this->check()));
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

        $result = $this->check();
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

        $result = $this->check();
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

        $result = $this->check();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldNotFoundInAnyTrackerFault::class, $result->error);
    }
}
