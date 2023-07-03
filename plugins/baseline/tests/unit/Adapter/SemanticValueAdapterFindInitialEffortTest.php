<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use Tracker_FormElement_Field;

final class SemanticValueAdapterFindInitialEffortTest extends SemanticValueAdapterTestCase
{
    public function testFindInitialEffort(): void
    {
        $this->changeset->method('getTracker')->willReturn($this->tracker);

        $field = $this->createMock(Tracker_FormElement_Field::class);
        $field->method('userCanRead')->willReturn(true);

        $this->semantic_field_repository
            ->method('findInitialEffortByTracker')
            ->with($this->tracker)
            ->willReturn($field);

        $this->changeset->method('getValue')
            ->with($field)
            ->willReturn($this->mockChangesetValue(5));

        $title = $this->adapter->findInitialEffort($this->changeset, $this->current_tuleap_user);

        self::assertEquals(5, $title);
    }

    public function testFindInitialEffortReturnNullWhenNotAuthorized(): void
    {
        $this->changeset->method('getTracker')->willReturn($this->tracker);

        $field = $this->createMock(Tracker_FormElement_Field::class);
        $field->method('userCanRead')->willReturn(false);

        $this->semantic_field_repository
            ->method('findInitialEffortByTracker')
            ->with($this->tracker)
            ->willReturn($field);

        $title = $this->adapter->findInitialEffort($this->changeset, $this->current_tuleap_user);

        self::assertNull($title);
    }

    public function testFindInitialEffortReturnsNullWhenNoInitialEffortField(): void
    {
        $this->changeset->method('getTracker')->willReturn($this->tracker);

        $this->semantic_field_repository
            ->method('findInitialEffortByTracker')
            ->with($this->tracker)
            ->willReturn(null);

        $title = $this->adapter->findInitialEffort($this->changeset, $this->current_tuleap_user);

        self::assertNull($title);
    }

    public function testFindInitialEffortReturnsNullWhenNoValueForGivenChangeset(): void
    {
        $this->changeset->method('getTracker')->willReturn($this->tracker);

        $field = $this->createMock(Tracker_FormElement_Field::class);
        $field->method('userCanRead')->willReturn(false);

        $this->semantic_field_repository
            ->method('findInitialEffortByTracker')
            ->with($this->tracker)
            ->willReturn($field);

        $this->changeset->method('getValue')
            ->with($field)
            ->willReturn(null);

        $title = $this->adapter->findInitialEffort($this->changeset, $this->current_tuleap_user);

        self::assertNull($title);
    }
}
