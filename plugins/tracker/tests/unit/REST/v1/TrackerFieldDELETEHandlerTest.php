<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\CannotRemoveFormElementFault;
use Tuleap\Tracker\FormElement\Field\FieldUsedInSemanticsFault;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Semantic\CollectionOfSemanticsUsingAParticularTrackerField;
use Tuleap\Tracker\Test\Stub\DeleteFormElementStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerFieldDELETEHandlerTest extends TestCase
{
    public function testDeleteField(): void
    {
        $usages = $this->createMock(CollectionOfSemanticsUsingAParticularTrackerField::class);
        $usages->method('areThereSemanticsUsingField')->willReturn(false);

        $field = $this->createMock(TrackerField::class);
        $field->method('getId')->willReturn(101);
        $field->method('getUsagesInSemantics')->willReturn($usages);
        $field->method('canBeRemovedFromUsage')->willReturn(true);
        $field->expects($this->once())->method('delete');

        $result = new TrackerFieldDELETEHandler(
            DeleteFormElementStub::withSuccessDeletion(),
            new DBTransactionExecutorPassthrough(),
        )->handle($field);

        self::assertTrue(Result::isOk($result));
    }

    public function testFaultWhenDeletionFails(): void
    {
        $usages = $this->createMock(CollectionOfSemanticsUsingAParticularTrackerField::class);
        $usages->method('areThereSemanticsUsingField')->willReturn(false);

        $field = $this->createMock(TrackerField::class);
        $field->method('getId')->willReturn(101);
        $field->method('getUsagesInSemantics')->willReturn($usages);
        $field->method('canBeRemovedFromUsage')->willReturn(true);
        $field->expects($this->once())->method('delete');

        $result = new TrackerFieldDELETEHandler(
            DeleteFormElementStub::withFailedDeletion(),
            new DBTransactionExecutorPassthrough(),
        )->handle($field);

        self::assertTrue(Result::isErr($result));
    }

    public function testFaultWhenFieldIsUsedInSemantics(): void
    {
        $usages = $this->createMock(CollectionOfSemanticsUsingAParticularTrackerField::class);
        $usages->method('areThereSemanticsUsingField')->willReturn(true);
        $usages->method('getUsages')->willReturn('');

        $field = $this->createMock(TrackerField::class);
        $field->method('getUsagesInSemantics')->willReturn($usages);
        $field->method('canBeRemovedFromUsage')->willReturn(true);

        $result = new TrackerFieldDELETEHandler(
            DeleteFormElementStub::withSuccessDeletion(),
            new DBTransactionExecutorPassthrough(),
        )->handle($field);

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldUsedInSemanticsFault::class, $result->error);
    }

    public function testFaultWhenFieldCannotBeRemoved(): void
    {
        $usages = $this->createMock(CollectionOfSemanticsUsingAParticularTrackerField::class);
        $usages->method('areThereSemanticsUsingField')->willReturn(false);

        $field = $this->createMock(TrackerField::class);
        $field->method('getUsagesInSemantics')->willReturn($usages);
        $field->method('canBeRemovedFromUsage')->willReturn(false);
        $field->method('getCannotRemoveMessage')->willReturn('Cannot remove form element');

        $result = new TrackerFieldDELETEHandler(
            DeleteFormElementStub::withSuccessDeletion(),
            new DBTransactionExecutorPassthrough(),
        )->handle($field);

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CannotRemoveFormElementFault::class, $result->error);
    }
}
