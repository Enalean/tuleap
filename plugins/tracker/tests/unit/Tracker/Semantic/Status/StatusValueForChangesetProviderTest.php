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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Status;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Artifact\Artifact;

class StatusValueForChangesetProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var StatusValueForChangesetProvider
     */
    private $provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Semantic_Status
     */
    private $semantic;

    protected function setUp(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset->shouldReceive('getArtifact')->andReturn($artifact);

        $this->user = Mockery::mock(PFUser::class);

        $this->semantic = Mockery::mock(\Tracker_Semantic_Status::class);

        $this->provider = Mockery::mock(StatusValueForChangesetProvider::class . '[loadSemantic]');
        $this->provider->shouldAllowMockingProtectedMethods();
        $this->provider->shouldReceive('loadSemantic')->andReturn($this->semantic);
    }

    public function testReturnsNullIfNoFieldForStatus()
    {
        $this->semantic->shouldReceive('getField')->once()->andReturnNull();

        $this->assertNull($this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }

    public function testReturnsNullIfUserCannotReadStatus()
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->semantic->shouldReceive('getField')->once()->andReturn($field);

        $field->shouldReceive('userCanRead')->with($this->user)->once()->andReturnFalse();

        $this->assertNull($this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }

    public function testReturnsNullIfNoChangesetValue()
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->semantic->shouldReceive('getField')->once()->andReturn($field);

        $field->shouldReceive('userCanRead')->with($this->user)->once()->andReturnTrue();

        $this->changeset->shouldReceive('getValue')->with($field)->andReturnNull();

        $this->assertNull($this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }

    public function testReturnsNullIfNoValueForField()
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->semantic->shouldReceive('getField')->once()->andReturn($field);

        $field->shouldReceive('userCanRead')->with($this->user)->once()->andReturnTrue();

        $value = Mockery::mock(\Tracker_Artifact_ChangesetValue_List::class);
        $this->changeset->shouldReceive('getValue')->with($field)->andReturn($value);

        $value->shouldReceive('getListValues')->once()->andReturn([]);

        $this->assertNull($this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }

    public function testReturnsTheFirstValue()
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->semantic->shouldReceive('getField')->once()->andReturn($field);

        $field->shouldReceive('userCanRead')->with($this->user)->once()->andReturnTrue();

        $value = Mockery::mock(\Tracker_Artifact_ChangesetValue_List::class);
        $this->changeset->shouldReceive('getValue')->with($field)->andReturn($value);

        $todo = Mockery::mock(\Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $done = Mockery::mock(\Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value->shouldReceive('getListValues')->once()->andReturn([$todo, $done]);

        $this->assertSame($todo, $this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }
}
