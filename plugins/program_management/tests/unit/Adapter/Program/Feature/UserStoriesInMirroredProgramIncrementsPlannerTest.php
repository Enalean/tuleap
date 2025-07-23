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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchFeaturesInChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchArtifactsLinks;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\PlanUserStoryInOneMirrorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementFromTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchArtifactsLinksStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchFeaturesInChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchFeaturesStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserStoriesInMirroredProgramIncrementsPlannerTest extends TestCase
{
    private const MIRRORED_TIMEBOX_ID = 666;
    private const USER_STORY_ID       = 1234;
    private const FEATURE_ID          = 101;
    private const TEAM_ID             = 172;

    private ProgramIncrementChanged $program_increment_changed;

    private PlanUserStoryInOneMirrorStub $plan_user_story_in_one_mirror_stub;

    #[\Override]
    protected function setUp(): void
    {
        $update                                   = ProgramIncrementUpdateBuilder::build();
        $this->program_increment_changed          =  ProgramIncrementChanged::fromUpdate($update);
        $this->plan_user_story_in_one_mirror_stub = PlanUserStoryInOneMirrorStub::build();
    }

    private function getPlanner(
        SearchArtifactsLinks $search_artifacts_links,
        VerifyIsVisibleArtifact $verify_is_visible_artifact,
        SearchFeaturesInChangeset $search_features_in_changeset,
    ): UserStoriesInMirroredProgramIncrementsPlanner {
        $logger = new NullLogger();
        return new UserStoriesInMirroredProgramIncrementsPlanner(
            new DBTransactionExecutorPassthrough(),
            $search_artifacts_links,
            SearchMirroredTimeboxesStub::withIds(self::MIRRORED_TIMEBOX_ID),
            $verify_is_visible_artifact,
            SearchFeaturesStub::withFeatureIds(self::FEATURE_ID),
            $logger,
            $search_features_in_changeset,
            RetrieveMirroredProgramIncrementFromTeamStub::withIds(self::MIRRORED_TIMEBOX_ID),
            $this->plan_user_story_in_one_mirror_stub
        );
    }

    public function testItAddLinksToMirroredMilestones(): void
    {
        $feature_to_remove_id            = 60000;
        $child_of_feature_to_remove_link = ['id' => 20000, 'project_id' => self::TEAM_ID];
        $raw_link                        = ['id' => self::USER_STORY_ID, 'project_id' => self::TEAM_ID];
        $search_artifacts_links          = SearchArtifactsLinksStub::build();
        $search_artifacts_links->withArtifactsLinkedToFeature(self::FEATURE_ID, [$raw_link]);
        $search_artifacts_links->withArtifactsLinkedToFeature($feature_to_remove_id, [$child_of_feature_to_remove_link]);

        $search_features_in_changeset_stub = SearchFeaturesInChangesetStub::build();
        $search_features_in_changeset_stub->withChangesetsAndFeatures(
            $this->program_increment_changed->changeset,
            [self::FEATURE_ID]
        );
        $search_features_in_changeset_stub->withChangesetsAndFeatures(
            $this->program_increment_changed->old_changeset,
            [$feature_to_remove_id]
        );

        $this->getPlanner(
            $search_artifacts_links,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $search_features_in_changeset_stub
        )->plan($this->program_increment_changed);

        self::assertEquals([self::USER_STORY_ID], $this->plan_user_story_in_one_mirror_stub->getFeaturedPlannedInMirrors(self::MIRRORED_TIMEBOX_ID));
        self::assertEquals([$child_of_feature_to_remove_link['id']], $this->plan_user_story_in_one_mirror_stub->getFeaturedUnplannedFromMirrors(self::MIRRORED_TIMEBOX_ID));
    }

    public function testItThrowsWhenUserCannotSeeOneMirroredProgramIncrement(): void
    {
        $this->expectException(MirroredProgramIncrementIsNotVisibleException::class);
        $this->getPlanner(
            SearchArtifactsLinksStub::build(),
            VerifyIsVisibleArtifactStub::withNoVisibleArtifact(),
            SearchFeaturesInChangesetStub::build()
        )->plan($this->program_increment_changed);
    }

    public function testItAddLinksToADeterminedTeamMilestone(): void
    {
        $team_identifier                 = TeamIdentifierBuilder::buildWithId(self::TEAM_ID);
        $feature_to_remove_id            = 60000;
        $child_of_feature_to_remove_link = ['id' => 20000, 'project_id' => self::TEAM_ID];
        $raw_link                        = ['id' => self::USER_STORY_ID, 'project_id' => self::TEAM_ID];
        $search_artifacts_links          = SearchArtifactsLinksStub::build();
        $search_artifacts_links->withArtifactsLinkedToFeature(self::FEATURE_ID, [$raw_link]);
        $search_artifacts_links->withArtifactsLinkedToFeature($feature_to_remove_id, [$child_of_feature_to_remove_link]);

        $search_features_in_changeset_stub = SearchFeaturesInChangesetStub::build();
        $search_features_in_changeset_stub->withChangesetsAndFeatures(
            $this->program_increment_changed->changeset,
            [self::FEATURE_ID]
        );
        $search_features_in_changeset_stub->withChangesetsAndFeatures(
            $this->program_increment_changed->old_changeset,
            [$feature_to_remove_id]
        );

        $this->getPlanner(
            $search_artifacts_links,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $search_features_in_changeset_stub
        )->planForATeam($this->program_increment_changed, $team_identifier);

        self::assertEquals([self::USER_STORY_ID], $this->plan_user_story_in_one_mirror_stub->getFeaturedPlannedInMirrors(self::MIRRORED_TIMEBOX_ID));
        self::assertEquals([$child_of_feature_to_remove_link['id']], $this->plan_user_story_in_one_mirror_stub->getFeaturedUnplannedFromMirrors(self::MIRRORED_TIMEBOX_ID));
    }
}
