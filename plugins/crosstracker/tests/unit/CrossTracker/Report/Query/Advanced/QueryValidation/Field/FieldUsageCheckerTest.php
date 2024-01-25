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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Field;

use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeIsNotSupportedFault;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypesAreIncompatibleFault;
use Tuleap\CrossTracker\Tests\Builders\InvalidSearchableCollectorParametersBuilder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Test\Builders\TrackerExternalFormElementBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementFloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class FieldUsageCheckerTest extends TestCase
{
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
            TrackerFormElementIntFieldBuilder::anIntField(841)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementFloatFieldBuilder::aFloatField(805)
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

        $checker = new FieldUsageChecker($this->fields_retriever, RetrieveFieldTypeStub::withDetectionOfType());
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
            TrackerFormElementIntFieldBuilder::anIntField(308)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementStringFieldBuilder::aStringField(358)
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
            TrackerExternalFormElementBuilder::anExternalField(569)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementIntFieldBuilder::anIntField(308)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );

        $result = $this->check();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypeIsNotSupportedFault::class, $result->error);
    }
}
