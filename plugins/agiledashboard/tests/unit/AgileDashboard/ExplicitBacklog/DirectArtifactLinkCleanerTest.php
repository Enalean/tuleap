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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tracker;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class DirectArtifactLinkCleanerTest extends TestCase
{
    private DirectArtifactLinkCleaner $cleaner;
    private Planning_MilestoneFactory&MockObject $milestone_factory;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private ArtifactsInExplicitBacklogDao&MockObject $artifacts_in_explicit_backlog_dao;
    private Tracker $tracker;
    private Artifact $artifact;
    private PFUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->milestone_factory                 = $this->createMock(Planning_MilestoneFactory::class);
        $this->explicit_backlog_dao              = $this->createMock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);

        $this->cleaner = new DirectArtifactLinkCleaner(
            $this->milestone_factory,
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao
        );

        $this->tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();

        $this->artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->user     = UserTestBuilder::buildWithDefaults();
    }

    public function testItDoesNothingIfProjectDoesNotUseExplicitBacklogMangement(): void
    {
        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->willReturn(false);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $this->artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactIsNotAMilestone(): void
    {
        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->willReturn(true);

        $this->milestone_factory->expects(self::once())->method('getBareMilestoneByArtifact')
            ->willReturn(null);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $this->artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactDoesNotHaveAnArtifactLinkField(): void
    {
        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->willReturn(true);

        $this->milestone_factory->expects(self::once())->method('getBareMilestoneByArtifact')
            ->willReturn($this->createMock(Planning_Milestone::class));

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->expects(self::once())->method('getAnArtifactLinkField')
            ->willReturn(null);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactDoesNotHaveALastChangeset(): void
    {
        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->willReturn(true);

        $this->milestone_factory->expects(self::once())->method('getBareMilestoneByArtifact')
            ->willReturn($this->createMock(Planning_Milestone::class));

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->expects(self::once())->method('getAnArtifactLinkField')
            ->willReturn($this->createMock(Tracker_FormElement_Field_ArtifactLink::class));
        $artifact->expects(self::once())->method('getLastChangeset')
            ->willReturn(null);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactDoesNotHaveALastChangesetValue(): void
    {
        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->willReturn(true);

        $this->milestone_factory->expects(self::once())->method('getBareMilestoneByArtifact')
            ->willReturn($this->createMock(Planning_Milestone::class));

        $artifact_link_field = $this->createMock(Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link_field->method('getId')->willReturn(124);
        $changeset = ChangesetTestBuilder::aChangeset('1')->build();

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->expects(self::once())->method('getAnArtifactLinkField')
            ->willReturn($artifact_link_field);
        $artifact->expects(self::once())->method('getLastChangeset')
            ->willReturn($changeset);

        $changeset->setFieldValue($artifact_link_field);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $artifact,
            $this->user
        );
    }

    public function testItDoesNothingIfArtifactDoesNotHaveLinkedArtifacts(): void
    {
        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->willReturn(true);

        $this->milestone_factory->expects(self::once())->method('getBareMilestoneByArtifact')
            ->willReturn($this->createMock(Planning_Milestone::class));

        $artifact_link_field = $this->createMock(Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link_field->method('getId')->willReturn(124);
        $changeset       = ChangesetTestBuilder::aChangeset('1')->build();
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(12, $changeset, $artifact_link_field)->build();
        $changeset->setFieldValue($artifact_link_field, $changeset_value);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(458);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->expects(self::once())->method('getAnArtifactLinkField')
            ->willReturn($artifact_link_field);
        $artifact->expects(self::once())->method('getLastChangeset')
            ->willReturn($changeset);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('cleanUpDirectlyPlannedItemsInArtifact');

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $artifact,
            $this->user
        );
    }

    public function testItCleansArtifactInExplicitBacklogThatAreManuallyPlanned(): void
    {
        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->willReturn(true);

        $this->milestone_factory->expects(self::once())->method('getBareMilestoneByArtifact')
            ->willReturn($this->createMock(Planning_Milestone::class));

        $artifact_link_field = $this->createMock(Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link_field->method('getId')->willReturn(124);
        $changeset       = ChangesetTestBuilder::aChangeset('1')->build();
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(12, $changeset, $artifact_link_field)
            ->withLinks([
                450 => new Tracker_ArtifactLinkInfo(450, '', 101, 1, 1, ''),
                452 => new Tracker_ArtifactLinkInfo(452, '', 101, 1, 1, ''),
            ])->build();
        $changeset->setFieldValue($artifact_link_field, $changeset_value);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(458);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->expects(self::once())->method('getAnArtifactLinkField')
            ->willReturn($artifact_link_field);
        $artifact->expects(self::once())->method('getLastChangeset')
            ->willReturn($changeset);

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('cleanUpDirectlyPlannedItemsInArtifact')
            ->with(458, [450, 452]);

        $this->cleaner->cleanDirectlyMadeArtifactLinks(
            $artifact,
            $this->user
        );
    }
}
