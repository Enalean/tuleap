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

use Mockery;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\FeatureNotAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\Plan;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class UserStoryRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsLinkedToParentDao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var UserStoryRepresentationBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BackgroundColorRetriever
     */
    private $retrieve_background;

    protected function setUp(): void
    {
        $this->dao                 = \Mockery::mock(ArtifactsLinkedToParentDao::class);
        $this->artifact_factory    = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->user                = \Mockery::mock(\PFUser::class);
        $this->retrieve_background = \Mockery::mock(BackgroundColorRetriever::class);

        $plan_store = new class () implements PlanStore {
            public function isPlannable(int $plannable_tracker_id): bool
            {
                assert($plannable_tracker_id === 56 || $plannable_tracker_id === 666);

                if ($plannable_tracker_id === 56) {
                    return true;
                }

                return false;
            }

            public function save(Plan $plan): void
            {
                throw new \LogicException("Not implemented");
            }

            public function isPartOfAPlan(ProgramTracker $tracker_data): bool
            {
                throw new \LogicException("Not implemented");
            }

            public function getProgramIncrementTrackerId(int $project_id): ?int
            {
                throw new \LogicException("Not implemented");
            }

            public function getProgramIncrementLabels(int $program_increment_tracker_id): ?array
            {
                throw new \LogicException("Not implemented");
            }
        };

        $this->builder = new UserStoryRepresentationBuilder(
            $this->dao,
            $this->artifact_factory,
            $plan_store,
            $this->retrieve_background
        );
    }

    public function testGetBacklogItemsThatUserCanSee(): void
    {
        $this->dao
            ->shouldReceive("getChildrenOfFeatureInTeamProjects")
            ->with(10)
            ->once()
            ->andReturn([['children_id' => 125], ['children_id' => 126], ['children_id' => 666]]);

        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, 10)
            ->once()
            ->andReturn(\Mockery::mock(Artifact::class, ['getTrackerId' => 56]));

        $artifact_125 = $this->buildArtifact(125);
        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, 125)
            ->once()
            ->andReturn($artifact_125);

        $artifact_126 = $this->buildArtifact(126);
        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, 126)
            ->once()
            ->andReturn($artifact_126);

        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, 666)
            ->once()
            ->andReturnNull();

        $this->retrieve_background
            ->shouldReceive('retrieveBackgroundColor')
            ->with($artifact_125, $this->user)
            ->once()
            ->andReturn(new BackgroundColor("lake-placid-blue"));

        $this->retrieve_background
            ->shouldReceive('retrieveBackgroundColor')
            ->with($artifact_126, $this->user)
            ->once()
            ->andReturn(new BackgroundColor("fiesta-red"));

        $children = $this->builder->buildFeatureStories(10, $this->user);

        self::assertCount(2, $children);

        self::assertEquals(125, $children[0]->id);
        self::assertEquals('Title', $children[0]->title);
        self::assertEquals('trackers?aid=125', $children[0]->uri);
        self::assertEquals('story #125', $children[0]->xref);
        self::assertEquals(true, $children[0]->is_open);
        self::assertEquals(true, $children[0]->project->id);
        self::assertEquals("Project", $children[0]->project->label);
        self::assertEquals("projects/100", $children[0]->project->uri);
        self::assertEquals("lake-placid-blue", $children[0]->background_color);
        self::assertEquals("inca-silver", $children[0]->tracker->color_name);

        self::assertEquals(126, $children[1]->id);
        self::assertEquals('Title', $children[1]->title);
        self::assertEquals('trackers?aid=126', $children[1]->uri);
        self::assertEquals('story #126', $children[1]->xref);
        self::assertEquals(true, $children[1]->is_open);
        self::assertEquals(true, $children[1]->project->id);
        self::assertEquals("Project", $children[1]->project->label);
        self::assertEquals("projects/100", $children[1]->project->uri);
        self::assertEquals("fiesta-red", $children[1]->background_color);
        self::assertEquals("inca-silver", $children[1]->tracker->color_name);
    }

    public function testThrowErrorIfUserCanNotSeeFeature(): void
    {
        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, 10)
            ->once()
            ->andReturnNull();

        $this->expectException(FeatureNotAccessException::class);
        $this->builder->buildFeatureStories(10, $this->user);
    }

    public function testThrowErrorIfFeatureTrackerIsNotPlannable(): void
    {
        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, 10)
            ->once()
            ->andReturn(\Mockery::mock(Artifact::class, ['getTrackerId' => 666]));

        $this->expectException(FeatureIsNotPlannableException::class);
        $this->builder->buildFeatureStories(10, $this->user);
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    private function buildArtifact(int $id)
    {
        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->once()->andReturn($id);
        $artifact->shouldReceive('getUri')->once()->andReturn('trackers?aid=' . $id);
        $artifact->shouldReceive('getXRef')->once()->andReturn('story #' . $id);
        $artifact->shouldReceive('getTitle')->once()->andReturn("Title");
        $artifact->shouldReceive('isOpen')->once()->andReturn(true);
        $artifact->shouldReceive('getTracker')
            ->twice()
            ->andReturn(
                TrackerTestBuilder::aTracker()
                    ->withProject(
                        ProjectTestBuilder::aProject()
                            ->withId(100)
                            ->withPublicName("Project")
                            ->build()
                    )
                ->build()
            );

        return $artifact;
    }
}
