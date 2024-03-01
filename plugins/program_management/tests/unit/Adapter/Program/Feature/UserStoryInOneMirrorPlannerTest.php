<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Tests\Builder\MirroredProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchArtifactsLinksStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;

final class UserStoryInOneMirrorPlannerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RetrieveFullArtifactStub $retrieve_full_artifact;
    private NullLogger $logger;
    private UserStoryInOneMirrorPlanner $planner;
    private CreateNewChangesetStub $create_new_changeset;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Artifact
     */
    private $artifact;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->withId(1)->build())->build());
        $this->artifact->method('getId')->willReturn(1234);

        $this->logger               = new NullLogger();
        $this->create_new_changeset = CreateNewChangesetStub::withNullReturnChangeset();
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);

        $this->retrieve_full_artifact = RetrieveFullArtifactStub::withArtifact($this->artifact);
        $this->planner                = new UserStoryInOneMirrorPlanner(
            $this->retrieve_full_artifact,
            $this->logger,
            $this->create_new_changeset,
            RetrieveUserStub::withGenericUser(),
            $this->form_element_factory
        );
    }

    public function testItAddLinksToMirroredMilestones(): void
    {
        $program_identifier         = ProgramIncrementIdentifierBuilder::buildWithId(1);
        $mirrored_program_increment = MirroredProgramIncrementIdentifierBuilder::buildWithId(2);
        $feature_change             = FeaturePlanChange::fromRaw(SearchArtifactsLinksStub::build(), [], [], 9);

        $this->form_element_factory->method('getAnArtifactLinkField')->willReturn(
            ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build()
        );

        $this->planner->planInOneMirror($program_identifier, $mirrored_program_increment, $feature_change, UserIdentifierStub::withId(666));

        self::assertEquals(1, $this->create_new_changeset->getCallsCount());
    }

    public function testItDoesNothingWhenArtifactLinkIsNotFound(): void
    {
        $program_identifier         = ProgramIncrementIdentifierBuilder::buildWithId(1);
        $mirrored_program_increment = MirroredProgramIncrementIdentifierBuilder::buildWithId(2);
        $feature_change             = FeaturePlanChange::fromRaw(SearchArtifactsLinksStub::build(), [], [], 9);

        $this->form_element_factory->method('getAnArtifactLinkField')->willReturn(null);

        $this->artifact->expects(self::never())->method('createNewChangeset');
        $this->planner->planInOneMirror($program_identifier, $mirrored_program_increment, $feature_change, UserIdentifierStub::withId(666));

        self::assertEquals(0, $this->create_new_changeset->getCallsCount());
    }
}
