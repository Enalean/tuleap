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

namespace Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select;

use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypeIsNotSupportedFault;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypesAreIncompatibleFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DuckTypedFieldSelectTest extends TestCase
{
    private const string FIELD_NAME     = 'initial_effort';
    private const int FIRST_TRACKER_ID  = 14;
    private const int SECOND_TRACKER_ID = 74;
    private const int INT_FIELD_ID      = 459;
    private const int FLOAT_FIELD_ID    = 643;
    private \Tuleap\Tracker\Tracker $first_tracker;
    private \Tuleap\Tracker\Tracker $second_tracker;
    /** @var list<\Tuleap\Tracker\FormElement\Field\TrackerField> */
    private array $fields;

    #[\Override]
    protected function setUp(): void
    {
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(self::FIRST_TRACKER_ID)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(self::SECOND_TRACKER_ID)->build();

        $this->fields = [
            IntegerFieldBuilder::anIntField(self::INT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->build(),
            FloatFieldBuilder::aFloatField(self::FLOAT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->build(),
        ];
    }

    /**
     * @return Ok<DuckTypedFieldSelect>|Err<Fault>
     */
    private function build(): Ok|Err
    {
        return DuckTypedFieldSelect::build(
            new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withDetectionOfType()),
            self::FIELD_NAME,
            $this->fields,
            [self::FIRST_TRACKER_ID, self::SECOND_TRACKER_ID],
        );
    }

    public function testItBuildsWhenFieldHasCompatibleTypesInAllTrackers(): void
    {
        $result = $this->build();

        self::assertTrue(Result::isOk($result));
        $field = $result->value;
        self::assertInstanceOf(DuckTypedFieldSelect::class, $field);
        self::assertSame(self::FIELD_NAME, $field->name);
        self::assertSame(DuckTypedFieldTypeSelect::NUMERIC, $field->type);
        self::assertSame([self::INT_FIELD_ID, self::FLOAT_FIELD_ID], $field->field_ids);
    }

    public function testItReturnsUnknownWhenFieldIsNotFoundInAnyTracker(): void
    {
        $this->fields = [];

        $result = $this->build();

        self::assertTrue(Result::isOk($result));
        $field = $result->value;
        self::assertInstanceOf(DuckTypedFieldSelect::class, $field);
        self::assertSame(self::FIELD_NAME, $field->name);
        self::assertSame(DuckTypedFieldTypeSelect::UNKNOWN, $field->type);
        self::assertSame([], $field->field_ids);
    }

    public function testItReturnsErrWhenFirstTypeIsNotSupported(): void
    {
        $this->fields = [
            ExternalFieldBuilder::anExternalField(91)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->build(),
            FloatFieldBuilder::aFloatField(self::FLOAT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->build(),
        ];

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypeIsNotSupportedFault::class, $result->error);
    }

    public function testItReturnsErrWhenSecondFieldTypeIsNotSupported(): void
    {
        $this->fields = [
            IntegerFieldBuilder::anIntField(self::INT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->build(),
            ExternalFieldBuilder::anExternalField(91)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->build(),
        ];

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypesAreIncompatibleFault::class, $result->error);
    }

    public function testItReturnsErrWhenFieldHasAnIncompatibleTypeInSecondTracker(): void
    {
        $this->fields = [
            IntegerFieldBuilder::anIntField(self::INT_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->build(),
            StringFieldBuilder::aStringField(92)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->build(),
        ];

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypesAreIncompatibleFault::class, $result->error);
    }
}
