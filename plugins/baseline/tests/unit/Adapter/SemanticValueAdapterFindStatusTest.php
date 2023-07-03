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

use Tracker_FormElement_Field_List;

final class SemanticValueAdapterFindStatusTest extends SemanticValueAdapterTestCase
{
    public function testFindStatus(): void
    {
        $this->changeset->method('getTracker')->willReturn($this->tracker);

        $field = $this->createMock(Tracker_FormElement_Field_List::class);
        $field->method('userCanRead')->willReturn(true);

        $this->semantic_field_repository
            ->method('findStatusByTracker')
            ->with($this->tracker)
            ->willReturn($field);

        $field->method('getFirstValueFor')
            ->with($this->changeset)
            ->willReturn('Custom status');

        $title = $this->adapter->findStatus($this->changeset, $this->current_tuleap_user);

        self::assertEquals('Custom status', $title);
    }

    public function testFindStatusReturnNullWhenNotAuthorized(): void
    {
        $this->changeset->method('getTracker')->willReturn($this->tracker);

        $field = $this->createMock(Tracker_FormElement_Field_List::class);
        $field->method('userCanRead')->willReturn(false);

        $this->semantic_field_repository
            ->method('findStatusByTracker')
            ->with($this->tracker)
            ->willReturn($field);

        $title = $this->adapter->findStatus($this->changeset, $this->current_tuleap_user);

        self::assertNull($title);
    }

    public function testFindStatusReturnsNullWhenNoStatusField(): void
    {
        $this->changeset->method('getTracker')->willReturn($this->tracker);

        $this->semantic_field_repository
            ->method('findStatusByTracker')
            ->with($this->tracker)
            ->willReturn(null);

        $title = $this->adapter->findStatus($this->changeset, $this->current_tuleap_user);

        self::assertNull($title);
    }

    public function testFindStatusReturnsNullWhenNoValueForGivenChangeset(): void
    {
        $this->changeset->method('getTracker')->willReturn($this->tracker);

        $field = $this->createMock(Tracker_FormElement_Field_List::class);
        $field->method('userCanRead')->willReturn(true);

        $this->semantic_field_repository
            ->method('findStatusByTracker')
            ->with($this->tracker)
            ->willReturn($field);

        $field->method('getFirstValueFor')
            ->with($this->changeset)
            ->willReturn(null);

        $title = $this->adapter->findStatus($this->changeset, $this->current_tuleap_user);

        self::assertNull($title);
    }
}
