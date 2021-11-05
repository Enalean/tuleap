<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlannableFeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Content\SearchUserStoryPlannedInIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\PlannableFeatureBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchUserStoryPlannedInIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\PHPUnit\TestCase;

final class UserStoryIdentifierTest extends TestCase
{
    private const USER_STORY_ID = 666;
    private const ITERATION_ID  = 777;
    private SearchChildrenOfFeature $user_story_searcher;
    private VerifyIsVisibleArtifact $verify_is_visible;
    private PlannableFeatureIdentifier $feature_identifier;
    private UserIdentifier $user_identifier;

    private array $visible_user_story;
    private array $invisible_user_story;
    private SearchMirroredTimeboxes $search_mirrored_timeboxes;
    private SearchUserStoryPlannedInIteration $search_user_story_planned_in_iteration;
    private IterationIdentifier $iteration_identifier;

    protected function setUp(): void
    {
        $this->visible_user_story   = ['children_id' => self::USER_STORY_ID];
        $this->invisible_user_story = ['children_id' => 404,];

        $this->verify_is_visible                      = VerifyIsVisibleArtifactStub::withVisibleIds(self::USER_STORY_ID);
        $this->search_mirrored_timeboxes              = SearchMirroredTimeboxesStub::withIds(self::ITERATION_ID);
        $this->search_user_story_planned_in_iteration = SearchUserStoryPlannedInIterationStub::withoutUserStory();

        $this->iteration_identifier = IterationIdentifierBuilder::buildWithId(2);
        $this->feature_identifier   = PlannableFeatureBuilder::build(1);
        $this->user_identifier      = UserIdentifierStub::buildGenericUser();
    }

    public function testSkipsIfUserCanNotSeeFromFeature(): void
    {
        $this->user_story_searcher = SearchChildrenOfFeatureStub::withChildren(
            [$this->invisible_user_story]
        );
        self::assertCount(
            0,
            UserStoryIdentifier::buildCollectionFromFeature(
                $this->user_story_searcher,
                $this->verify_is_visible,
                $this->feature_identifier,
                $this->user_identifier
            )
        );
    }

    public function testItBuildsUserStoryIdFromFeature(): void
    {
        $this->user_story_searcher = SearchChildrenOfFeatureStub::withChildren([$this->visible_user_story]);

        self::assertSame(
            self::USER_STORY_ID,
            UserStoryIdentifier::buildCollectionFromFeature(
                $this->user_story_searcher,
                $this->verify_is_visible,
                $this->feature_identifier,
                $this->user_identifier
            )[0]->getId()
        );
    }

    public function testSkipsIfUserCanNotSeeFromIteration(): void
    {
        $this->user_story_searcher = SearchChildrenOfFeatureStub::withChildren([404]);
        self::assertEmpty(
            UserStoryIdentifier::buildCollectionFromIteration(
                $this->search_user_story_planned_in_iteration,
                $this->search_mirrored_timeboxes,
                $this->verify_is_visible,
                $this->iteration_identifier,
                $this->user_identifier
            )
        );
    }

    public function testItBuildsAnEmptyArrayIfNoMirrorIsFound(): void
    {
        $this->search_mirrored_timeboxes = SearchMirroredTimeboxesStub::withNoMirrors();
        self::assertEmpty(
            UserStoryIdentifier::buildCollectionFromIteration(
                $this->search_user_story_planned_in_iteration,
                $this->search_mirrored_timeboxes,
                $this->verify_is_visible,
                $this->iteration_identifier,
                $this->user_identifier
            )
        );
    }

    public function testItBuildsAnEmptyArrayIfMirrorHasNoUserStory(): void
    {
        self::assertEmpty(
            UserStoryIdentifier::buildCollectionFromIteration(
                $this->search_user_story_planned_in_iteration,
                $this->search_mirrored_timeboxes,
                $this->verify_is_visible,
                $this->iteration_identifier,
                $this->user_identifier
            )
        );
    }

    public function testItBuildsAnEmptyArrayWhenUserCanNotSeeUserStory(): void
    {
        $this->search_user_story_planned_in_iteration = SearchUserStoryPlannedInIterationStub::withUserStory([self::USER_STORY_ID]);

        self::assertEmpty(
            UserStoryIdentifier::buildCollectionFromIteration(
                $this->search_user_story_planned_in_iteration,
                $this->search_mirrored_timeboxes,
                $this->verify_is_visible,
                $this->iteration_identifier,
                $this->user_identifier
            )
        );
    }

    public function testItBuildsUserStoryIdFromIteration(): void
    {
        $this->search_user_story_planned_in_iteration = SearchUserStoryPlannedInIterationStub::withUserStory([self::USER_STORY_ID]);
        $this->verify_is_visible                      = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();

        self::assertSame(
            self::USER_STORY_ID,
            UserStoryIdentifier::buildCollectionFromIteration(
                $this->search_user_story_planned_in_iteration,
                $this->search_mirrored_timeboxes,
                $this->verify_is_visible,
                $this->iteration_identifier,
                $this->user_identifier
            )[0]->getId()
        );
    }
}
