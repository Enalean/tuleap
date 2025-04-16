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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Tests\Builder\MirroredProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchArtifactsLinksStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserStoryInOneMirrorPlannerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID = 666;
    private UserStoryInOneMirrorPlanner $planner;
    private CreateNewChangesetStub $create_new_changeset;
    private Artifact&MockObject $artifact;
    private \Tracker_FormElementFactory&Stub $form_element_factory;

    protected function setUp(): void
    {
        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->withId(1)->build())->build());
        $this->artifact->method('getId')->willReturn(1234);

        $this->create_new_changeset = CreateNewChangesetStub::withNullReturnChangeset();
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);

        $this->planner = new UserStoryInOneMirrorPlanner(
            RetrieveFullArtifactStub::withArtifact($this->artifact),
            new NullLogger(),
            $this->create_new_changeset,
            RetrieveUserStub::withUser(UserTestBuilder::buildWithId(self::USER_ID)),
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

        $this->planner->planInOneMirror(
            $program_identifier,
            $mirrored_program_increment,
            $feature_change,
            UserIdentifierStub::withId(self::USER_ID)
        );

        self::assertSame(1, $this->create_new_changeset->getCallsCount());
    }

    public function testItDoesNothingWhenArtifactLinkIsNotFound(): void
    {
        $program_identifier         = ProgramIncrementIdentifierBuilder::buildWithId(1);
        $mirrored_program_increment = MirroredProgramIncrementIdentifierBuilder::buildWithId(2);
        $feature_change             = FeaturePlanChange::fromRaw(SearchArtifactsLinksStub::build(), [], [], 9);

        $this->form_element_factory->method('getAnArtifactLinkField')->willReturn(null);

        $this->artifact->expects($this->never())->method('createNewChangeset');
        $this->planner->planInOneMirror(
            $program_identifier,
            $mirrored_program_increment,
            $feature_change,
            UserIdentifierStub::withId(self::USER_ID)
        );

        self::assertSame(0, $this->create_new_changeset->getCallsCount());
    }
}
