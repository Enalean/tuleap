<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/SemanticValueAdapterTest.php';

use Mockery;
use Mockery\MockInterface;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_Artifact_ChangesetValue_Numeric;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List_Bind_StaticValue;

class SemanticValueAdapterFindInitialEffortTest extends SemanticValueAdapterTest
{
    public function testFindInitialEffort(): void
    {
        $this->changeset->shouldReceive('getTracker')->andReturn($this->tracker);

        $field = Mockery::mock(Tracker_FormElement_Field::class)
            ->shouldReceive('userCanRead')
            ->andReturn(true)
            ->getMock();

        $this->semantic_field_repository
            ->shouldReceive('findInitialEffortByTracker')
            ->with($this->tracker)
            ->andReturn($field);

        $this->changeset->shouldReceive('getValue')
            ->with($field)
            ->andReturn($this->mockChangesetValueNumeric(5));

        $title = $this->adapter->findInitialEffort($this->changeset, $this->current_user);

        $this->assertEquals(5, $title);
    }

    public function testFindInitialEffortWithValueList(): void
    {
        $this->changeset->shouldReceive('getTracker')->andReturn($this->tracker);

        $field = Mockery::mock(Tracker_FormElement_Field::class)
            ->shouldReceive('userCanRead')
            ->andReturn(true)
            ->getMock();

        $this->semantic_field_repository
            ->shouldReceive('findInitialEffortByTracker')
            ->with($this->tracker)
            ->andReturn($field);

        $this->changeset->shouldReceive('getValue')
            ->with($field)
            ->andReturn($this->mockChangesetValueListWithLabel("5"));

        $title = $this->adapter->findInitialEffort($this->changeset, $this->current_user);

        $this->assertEquals(5, $title);
    }

    public function testFindInitialEffortReturnNullWhenNotAuthorized(): void
    {
        $this->changeset->shouldReceive('getTracker')->andReturn($this->tracker);

        $field = Mockery::mock(Tracker_FormElement_Field::class)
            ->shouldReceive('userCanRead')
            ->andReturn(false)
            ->getMock();
        $this->semantic_field_repository
            ->shouldReceive('findInitialEffortByTracker')
            ->with($this->tracker)
            ->andReturn($field);

        $title = $this->adapter->findInitialEffort($this->changeset, $this->current_user);

        $this->assertNull($title);
    }

    public function testFindInitialEffortReturnsNullWhenNoInitialEffortField(): void
    {
        $this->changeset->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->semantic_field_repository
            ->shouldReceive('findInitialEffortByTracker')
            ->with($this->tracker)
            ->andReturn(null);

        $title = $this->adapter->findInitialEffort($this->changeset, $this->current_user);

        $this->assertNull($title);
    }

    public function testFindInitialEffortReturnsNullWhenNoValueForGivenChangeset(): void
    {
        $this->changeset->shouldReceive('getTracker')->andReturn($this->tracker);

        $field = Mockery::mock(Tracker_FormElement_Field::class)
            ->shouldReceive('userCanRead')
            ->andReturn(true)
            ->getMock();

        $this->semantic_field_repository
            ->shouldReceive('findInitialEffortByTracker')
            ->with($this->tracker)
            ->andReturn($field);

        $this->changeset->shouldReceive('getValue')
            ->with($field)
            ->andReturn(null);

        $title = $this->adapter->findInitialEffort($this->changeset, $this->current_user);

        $this->assertNull($title);
    }

    /**
     * @return Tracker_Artifact_ChangesetValue_Numeric|MockInterface
     */
    private function mockChangesetValueNumeric(float $value): Tracker_Artifact_ChangesetValue
    {
        return Mockery::mock(Tracker_Artifact_ChangesetValue_Numeric::class)
            ->shouldReceive(['getNumeric' => $value])
            ->getMock();
    }

    /**
     * @return Tracker_Artifact_ChangesetValue_List|MockInterface
     */
    private function mockChangesetValueListWithLabel(string $value): Tracker_Artifact_ChangesetValue
    {
        $value = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class)
            ->shouldReceive(['getLabel' => $value])
            ->getMock();
        return Mockery::mock(Tracker_Artifact_ChangesetValue_List::class)
            ->shouldReceive(['getListValues' => [$value]])
            ->getMock();
    }
}
