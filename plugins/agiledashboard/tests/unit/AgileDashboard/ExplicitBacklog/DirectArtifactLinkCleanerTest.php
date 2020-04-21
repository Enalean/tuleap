<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_FormElement_Field_ArtifactLink;

class DirectArtifactLinkCleanerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DirectArtifactLinkCleaner
     */
    private $cleaner;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->milestone_factory                 = Mockery::mock(Planning_MilestoneFactory::class);
        $this->explicit_backlog_dao              = Mockery::mock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);

        $this->cleaner = new DirectArtifactLinkCleaner(
            $this->milestone_factory,
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao
        );

        $tracker = Mockery::mock(Tracker::class)->shouldReceive('getGroupId')->andReturn('101')->getMock();

        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->user     = Mockery::mock(PFUser::class);

        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);
    }

    public function testItDoesNothingIfProjectDoesNotUseExplicitBacklogMangement(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->andReturnFalse();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $this->artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactIsNotAMilestone(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->andReturnTrue();

        $this->milestone_factory->shouldReceive('getBareMilestoneByArtifact')
            ->once()
            ->andReturnNull();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $this->artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactDoesNotHaveAnArtifactLinkField(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->andReturnTrue();

        $this->milestone_factory->shouldReceive('getBareMilestoneByArtifact')
            ->once()
            ->andReturn(Mockery::mock(Planning_Milestone::class));

        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->andReturnNull();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $this->artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactDoesNotHaveALastChangeset(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->andReturnTrue();

        $this->milestone_factory->shouldReceive('getBareMilestoneByArtifact')
            ->once()
            ->andReturn(Mockery::mock(Planning_Milestone::class));

        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->andReturn(Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class));

        $this->artifact->shouldReceive('getLastChangeset')
            ->once()
            ->andReturnNull();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $this->artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactDoesNotHaveALastChangesetValue(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->andReturnTrue();

        $this->milestone_factory->shouldReceive('getBareMilestoneByArtifact')
            ->once()
            ->andReturn(Mockery::mock(Planning_Milestone::class));

        $artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->andReturn($artifact_link_field);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')
            ->once()
            ->andReturn($changeset);

        $changeset->shouldReceive('getValue')
            ->with($artifact_link_field)
            ->once()
            ->andReturnNull();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $this->artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactDoesNotHaveLinkedArtifacts(): void
    {
        $this->artifact->shouldReceive('getId')->andReturn('458');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->andReturnTrue();

        $this->milestone_factory->shouldReceive('getBareMilestoneByArtifact')
            ->once()
            ->andReturn(Mockery::mock(Planning_Milestone::class));

        $artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->andReturn($artifact_link_field);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')
            ->once()
            ->andReturn($changeset);

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')
            ->with($artifact_link_field)
            ->once()
            ->andReturn($changeset_value);

        $changeset_value->shouldReceive('getArtifactIds')->once()->andReturn([]);

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $this->artifact,
            $this->user
        );
    }

    public function testItCleansArtifactInExplicitBacklogThatAreManuallyPlanned(): void
    {
        $this->artifact->shouldReceive('getId')->andReturn('458');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->andReturnTrue();

        $this->milestone_factory->shouldReceive('getBareMilestoneByArtifact')
            ->once()
            ->andReturn(Mockery::mock(Planning_Milestone::class));

        $artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->andReturn($artifact_link_field);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')
            ->once()
            ->andReturn($changeset);

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')
            ->with($artifact_link_field)
            ->once()
            ->andReturn($changeset_value);

        $changeset_value->shouldReceive('getArtifactIds')->once()->andReturn([450, 452]);

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('cleanUpDirectlyPlannedItemsInArtifact')
            ->once()
            ->with(
                458,
                [450, 452]
            );

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $this->artifact,
            $this->user
        );
    }
}
