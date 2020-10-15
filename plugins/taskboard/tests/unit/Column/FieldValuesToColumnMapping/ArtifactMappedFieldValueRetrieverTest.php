<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

final class ArtifactMappedFieldValueRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ArtifactMappedFieldValueRetriever */
    private $retriever;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|MappedFieldRetriever
     */
    private $mapped_field_retriever;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Planning_Milestone
     */
    private $milestone;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker
     */
    private $release_tracker;

    protected function setUp(): void
    {
        $this->mapped_field_retriever = M::mock(MappedFieldRetriever::class);
        $this->retriever              = new ArtifactMappedFieldValueRetriever($this->mapped_field_retriever);
        $this->release_tracker        = M::mock(\Tracker::class);
        $release_artifact             = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $release_artifact->shouldReceive('getTracker')
            ->andReturn($this->release_tracker);
        $this->milestone = M::mock(\Planning_Milestone::class);
        $this->milestone->shouldReceive('getArtifact')
            ->andReturn($release_artifact);
        $this->artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->user = M::mock(\PFUser::class);
    }

    public function testReturnsNullWhenNoMappedField(): void
    {
        $tracker = M::mock(\Tracker::class);
        $this->artifact->shouldReceive('getTracker')
            ->once()
            ->andReturn($tracker);
        $this->mapped_field_retriever->shouldReceive('getField')
            ->withArgs(
                function (TaskboardTracker $taskboard_tracker) use ($tracker) {
                    return $taskboard_tracker->getTracker() === $tracker;
                }
            )
            ->once()
            ->andReturnNull();

        $this->assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsNullWhenUserCantReadMappedField(): void
    {
        $mapped_field = $this->mockField();
        $mapped_field->shouldReceive('userCanRead')
            ->with($this->user)
            ->once()
            ->andReturnFalse();

        $this->assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsNullWhenNoLastChangeset(): void
    {
        $this->mockFieldUserCanRead();
        $this->artifact->shouldReceive('getLastChangeset')
            ->once()
            ->andReturnNull();

        $this->assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsNullWhenValueIsNotListValue(): void
    {
        $mapped_field   = $this->mockFieldUserCanRead();
        $last_changeset = M::mock(\Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')
            ->once()
            ->andReturn($last_changeset);
        $last_changeset->shouldReceive('getValue')
            ->with($mapped_field)
            ->once()
            ->andReturnNull();

        $this->assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsNullWhenValueIsEmpty(): void
    {
        $mapped_field   = $this->mockFieldUserCanRead();
        $last_changeset = M::mock(\Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')
            ->once()
            ->andReturn($last_changeset);
        $changeset_value = new \Tracker_Artifact_ChangesetValue_List(8608, $last_changeset, $mapped_field, false, []);
        $last_changeset->shouldReceive('getValue')
            ->once()
            ->andReturn($changeset_value);

        $this->assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsFirstValueOfMappedField(): void
    {
        $mapped_field   = $this->mockFieldUserCanRead();
        $last_changeset = M::mock(\Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')
            ->once()
            ->andReturn($last_changeset);
        $first_list_value  = new \Tracker_FormElement_Field_List_Bind_StaticValue(9074, 'On Going', '', 10, false);
        $second_list_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(9086, 'Blocked', '', 12, false);
        $changeset_value   = new \Tracker_Artifact_ChangesetValue_List(
            8608,
            $last_changeset,
            $mapped_field,
            false,
            [$first_list_value, $second_list_value]
        );
        $last_changeset->shouldReceive('getValue')
            ->once()
            ->andReturn($changeset_value);

        $this->assertSame(
            $first_list_value,
            $this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user)
        );
    }

    private function mockFieldUserCanRead(): M\MockInterface
    {
        $mapped_field = $this->mockField();
        $mapped_field->shouldReceive('userCanRead')
            ->with($this->user)
            ->once()
            ->andReturnTrue();
        return $mapped_field;
    }

    private function mockField(): M\MockInterface
    {
        $tracker = M::mock(\Tracker::class);
        $this->artifact->shouldReceive('getTracker')
            ->once()
            ->andReturn($tracker);

        $mapped_field = M::mock(\Tracker_FormElement_Field_Selectbox::class);
        $this->mapped_field_retriever->shouldReceive('getField')
            ->withArgs(
                function (TaskboardTracker $taskboard_tracker) use ($tracker) {
                    return $taskboard_tracker->getTracker() === $tracker;
                }
            )
            ->once()
            ->andReturn($mapped_field);

        return $mapped_field;
    }
}
