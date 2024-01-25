<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerExternalFormElementBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementFloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class DuckTypedFieldTest extends TestCase
{
    private const FIELD_NAME        = 'initial_effort';
    private const FIRST_TRACKER_ID  = 14;
    private const SECOND_TRACKER_ID = 74;
    private const INT_FIELD_ID      = 459;
    private const FLOAT_FIELD_ID    = 643;
    private RetrieveUsedFieldsStub $retrieve_used_fields;
    private \PFUser $user;
    private \Tracker $first_tracker;
    private \Tracker $second_tracker;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithId(156);

        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(self::FIRST_TRACKER_ID)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(self::SECOND_TRACKER_ID)->build();

        $this->retrieve_used_fields = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(self::INT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementFloatFieldBuilder::aFloatField(self::FLOAT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );
    }

    /**
     * @return Ok<DuckTypedField>|Err<Fault>
     */
    private function build(): Ok|Err
    {
        return DuckTypedField::build(
            $this->retrieve_used_fields,
            RetrieveFieldTypeStub::withDetectionOfType(),
            self::FIELD_NAME,
            [self::FIRST_TRACKER_ID, self::SECOND_TRACKER_ID],
            $this->user
        );
    }

    public function testItBuildsWhenFieldHasCompatibleTypesInAllTrackers(): void
    {
        $result = $this->build();

        self::assertTrue(Result::isOk($result));
        $field = $result->value;
        self::assertInstanceOf(DuckTypedField::class, $field);
        self::assertSame(self::FIELD_NAME, $field->name);
        self::assertSame(DuckTypedFieldType::NUMERIC, $field->type);
        self::assertSame([self::INT_FIELD_ID, self::FLOAT_FIELD_ID], $field->field_ids);
    }

    public function testItReturnsErrWhenFieldIsNotFoundInAnyTracker(): void
    {
        $this->retrieve_used_fields = RetrieveUsedFieldsStub::withNoFields();

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldNotFoundInAnyTrackerFault::class, $result->error);
    }

    public function testItReturnsErrWhenUserCannotReadFieldInAllTrackers(): void
    {
        $this->retrieve_used_fields = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(self::INT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, false)
                ->build(),
            TrackerFormElementFloatFieldBuilder::aFloatField(self::FLOAT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, false)
                ->build()
        );

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldNotFoundInAnyTrackerFault::class, $result->error);
    }

    public function testItReturnsErrWhenFirstTypeIsNotSupported(): void
    {
        $this->retrieve_used_fields = RetrieveUsedFieldsStub::withFields(
            TrackerExternalFormElementBuilder::anExternalField(91)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementFloatFieldBuilder::aFloatField(self::FLOAT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypeIsNotSupportedFault::class, $result->error);
    }

    public function testItReturnsErrWhenFieldHasAnIncompatibleTypeInSecondTracker(): void
    {
        $this->retrieve_used_fields = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(self::INT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerExternalFormElementBuilder::anExternalField(91)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypesAreIncompatibleFault::class, $result->error);
    }
}
