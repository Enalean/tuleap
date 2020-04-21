<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_ValueDao;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tracker_HierarchyFactory;
use Tuleap\Tracker\TrackerColor;

final class CatchLinkDirectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping
     */
    private $url_mapping;
    /**
     * @var int
     */
    private $modified_artifact_id;
    /**
     * @var Tracker_Artifact
     */
    private $modified_artifact;
    private $old_changeset;
    /**
     * @var int
     */
    private $new_changeset_id;
    /**
     * @var array
     */
    private $submitted_value;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $submitter;
    /**
     * @var int
     */
    private $new_changeset_value_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_123;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_124;
    /**
     * @var \Mockery\Mock
     */
    private $field;

    protected function setUp(): void
    {
        $this->url_mapping = \Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        Tracker_HierarchyFactory::setInstance($hierarchy_factory);
        $artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        Tracker_ArtifactFactory::setInstance($artifact_factory);

        $tracker        = $this->buildTracker(10);
        $parent_tracker = $this->buildTracker(11);
        $hierarchy_factory->shouldReceive('getChildren')->with($parent_tracker->getId())->andReturn([$tracker]);
        $hierarchy_factory->shouldReceive('getChildren')->with($tracker->getId())->andReturn([]);

        $this->modified_artifact_id = 223;
        $this->modified_artifact    = $this->buildArtifact($this->modified_artifact_id, $tracker);
        $this->old_changeset    = null;
        $this->new_changeset_id = 4444;
        $this->submitted_value  = [
            'new_values'     => '123, 124',
            'removed_values' => [
                345 => ['345'],
                346 => ['346']
            ]
        ];
        $this->submitter        = \Mockery::mock(\PFUser::class);
        $this->new_changeset_value_id = 66666;

        $this->artifact_123 = \Mockery::spy(Tracker_Artifact::class);
        $this->artifact_123->shouldReceive('getId')->andReturn(123);
        $this->artifact_123->shouldReceive('getTracker')->andReturn($parent_tracker);
        $this->artifact_123->shouldReceive('getTrackerId')->andReturn($parent_tracker->getId());
        $changeset = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturn(1231);
        $this->artifact_123->shouldReceive('getLastChangeset')->andReturn($changeset);

        $this->artifact_124 = \Mockery::spy(Tracker_Artifact::class);
        $this->artifact_124->shouldReceive('getId')->andReturn(124);
        $this->artifact_124->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact_124->shouldReceive('getTrackerId')->andReturn($tracker->getId());
        $changeset = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturn(1241);
        $this->artifact_124->shouldReceive('getLastChangeset')->andReturn($changeset);

        $artifact_factory->shouldReceive('getArtifactById')->with(123)->andReturn($this->artifact_123);
        $artifact_factory->shouldReceive('getArtifactById')->with(124)->andReturn($this->artifact_124);

        $this->field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $value_dao = \Mockery::spy(\Tracker_FormElement_Field_Value_ArtifactLinkDao::class);
        $this->field->shouldReceive('getValueDao')->andReturn($value_dao);
        $changeset_value_dao = \Mockery::spy(Tracker_Artifact_Changeset_ValueDao::class);
        $changeset_value_dao->shouldReceive('save')->andReturn($this->new_changeset_value_id);
        $this->field->shouldReceive('getChangesetValueDao')->andReturn($changeset_value_dao);
        $this->field->shouldReceive('userCanUpdate')->andReturn(true);
        $this->field->shouldReceive('isValid')->andReturn(true);
        $this->field->shouldReceive('getTracker')->andReturn($tracker);

        $this->field->shouldReceive('getProcessChildrenTriggersCommand')
            ->andReturn(\Mockery::spy(\Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand::class));
    }

    private function buildTracker(int $id): Tracker
    {
        return new Tracker(
            $id,
            102,
            'name',
            'description',
            'item_name',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            TrackerColor::default(),
            null
        );
    }

    private function buildArtifact(int $id, Tracker $tracker): Tracker_Artifact
    {
        $artifact = new Tracker_Artifact($id, $tracker->getId(), 101, 1, false);
        $artifact->setTracker($tracker);

        return $artifact;
    }

    protected function tearDown(): void
    {
        Tracker_HierarchyFactory::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function testPostponeSavesChangesetInSourceArtifact(): void
    {
        $this->artifact_123->shouldNotReceive('linkArtifact');

        // Then update the artifact with other links
        $remaining_submitted_value = [
            'new_values'               => '123, 124',
            'removed_values'           => [
                345 => ['345'],
                346 => ['346']
            ],
            'list_of_artifactlinkinfo' =>
                [
                    124 => Tracker_ArtifactLinkInfo::buildFromArtifact($this->artifact_124, '')
                ]
        ];
        $this->field
            ->shouldReceive('saveValue')
            ->with(
                $this->modified_artifact,
                $this->new_changeset_value_id,
                $remaining_submitted_value,
                null,
                $this->url_mapping
            )
            ->once();

        $this->field->saveNewChangeset(
            $this->modified_artifact,
            $this->old_changeset,
            $this->new_changeset_id,
            $this->submitted_value,
            $this->submitter,
            false,
            false,
            $this->url_mapping
        );
    }

    public function testSavesChangesetInSourceArtifact(): void
    {
        $this->artifact_123->shouldReceive('linkArtifact')->with($this->modified_artifact_id, $this->submitter)->once()->andReturn(true);

        $this->field->shouldReceive('saveValue')->once();

        $this->field->saveNewChangeset(
            $this->modified_artifact,
            $this->old_changeset,
            $this->new_changeset_id,
            $this->submitted_value,
            $this->submitter,
            false,
            false,
            $this->url_mapping
        );
        $this->field->postSaveNewChangeset(
            $this->modified_artifact,
            $this->submitter,
            \Mockery::spy(\Tracker_Artifact_Changeset::class)
        );
    }
}
