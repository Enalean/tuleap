<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Tracker;
use Tracker_ArtifactFactory;
use TrackerFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryURIStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsOpenStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsPlannableStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerIdStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class UserStoryRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 56;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private $tracker_factory;
    private SearchChildrenOfFeature $search_children_of_feature;
    private UserIdentifier $user;
    private RetrieveBackgroundColor $retrieve_background;
    private VerifyIsPlannable $plan_store;
    private Tracker $tracker;


    protected function setUp(): void
    {
        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withChildren(
            [
                ['children_id' => 125],
            ]
        );
        $this->artifact_factory           = $this->createMock(Tracker_ArtifactFactory::class);
        $this->tracker_factory            = $this->createMock(TrackerFactory::class);
        $this->user                       = UserIdentifierStub::buildGenericUser();
        $this->retrieve_background        = RetrieveBackgroundColorStub::withDefaults();

        $this->plan_store = VerifyIsPlannableStub::buildPlannableElement();

        $this->tracker = TrackerTestBuilder::aTracker()
                                           ->withProject(
                                               ProjectTestBuilder::aProject()
                                                                 ->withId(100)
                                                                 ->withPublicName("Project")
                                                                 ->build()
                                           )->build();
    }

    protected function getBuilder(): UserStoryRepresentationBuilder
    {
        return new UserStoryRepresentationBuilder(
            $this->search_children_of_feature,
            $this->artifact_factory,
            $this->plan_store,
            $this->retrieve_background,
            RetrieveUserStub::withGenericUser(),
            $this->tracker_factory,
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            RetrieveUserStoryTitleStub::withValue('Title'),
            RetrieveUserStoryURIStub::withId(125),
            RetrieveUserStoryCrossRefStub::withValues("story", 125),
            VerifyIsOpenStub::withOpen(),
            RetrieveTrackerIdStub::withDefault(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts()
        );
    }

    public function testGetBacklogItemsThatUserCanSee(): void
    {
        $artifact_125 = $this->createMock(Artifact::class);
        $artifact_125->expects(self::atLeast(1))->method('getTracker')->willReturn($this->tracker);
        $artifact_125->expects(self::atLeast(1))->method('getTrackerId')->willReturn($this->tracker->getId());

        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(
            $artifact_125
        );
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);

        $children = $this->getBuilder()->buildFeatureStories(10, $this->user);

        self::assertCount(1, $children);

        self::assertEquals(125, $children[0]->id);
        self::assertEquals('Title', $children[0]->title);
        self::assertEquals('/plugins/tracker/?aid=125', $children[0]->uri);
        self::assertEquals('story #125', $children[0]->xref);
        self::assertEquals(true, $children[0]->is_open);
        self::assertEquals(true, $children[0]->project->id);
        self::assertEquals("Project", $children[0]->project->label);
        self::assertEquals("projects/100", $children[0]->project->uri);
        self::assertEquals("lake-placid-blue", $children[0]->background_color);
        self::assertEquals("inca-silver", $children[0]->tracker->color_name);
    }

    public function testThrowErrorIfFeatureTrackerIsNotPlannable(): void
    {
        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with(self::isInstanceOf(\PFUser::class), 10)
            ->willReturn($this->createConfiguredMock(Artifact::class, ['getTracker' => TrackerTestBuilder::aTracker()->withId(666)->build(), 'getTrackerId' => 666]));

        $this->plan_store = VerifyIsPlannableStub::buildNotPlannableElement();

        $this->expectException(FeatureIsNotPlannableException::class);
        $this->getBuilder()->buildFeatureStories(10, $this->user);
    }
}
