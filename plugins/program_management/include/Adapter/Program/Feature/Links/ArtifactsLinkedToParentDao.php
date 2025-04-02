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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchFeaturesInChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchParentFeatureOfAUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchPlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchUnlinkedUserStoriesOfMirroredProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\VerifyIsLinkedToAnotherMilestone;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchArtifactsLinks;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIdentifier;

final class ArtifactsLinkedToParentDao extends DataAccessObject implements SearchArtifactsLinks, SearchUnlinkedUserStoriesOfMirroredProgramIncrement, SearchPlannedUserStory, SearchChildrenOfFeature, VerifyIsLinkedToAnotherMilestone, SearchParentFeatureOfAUserStory, SearchFeaturesInChangeset
{
    /**
     * @psalm-return array{id: int, project_id: int}[]
     */
    public function getArtifactsLinkedToId(int $artifact_id, int $program_increment_id): array
    {
        $sql = "SELECT linked_art.id, t.group_id as project_id
                FROM tracker_artifact AS parent_art
                         INNER JOIN tracker_field                           AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                         INNER JOIN tracker_changeset_value                 AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                         INNER JOIN tracker_changeset_value_artifactlink    AS artlink    ON (artlink.changeset_value_id = cv.id)
                         INNER JOIN tracker_artifact                        AS linked_art ON (linked_art.id = artlink.artifact_id)
                         INNER JOIN tracker                                 AS t          ON (t.id = linked_art.tracker_id)
                         INNER JOIN plugin_program_management_plan          AS plan       ON parent_art.tracker_id = plan.plannable_tracker_id
                         INNER JOIN plugin_program_management_program       AS program    ON plan.project_id = program.program_project_id
                WHERE parent_art.id  = ?
                  AND t.deletion_date IS NULL
                  AND program.program_increment_tracker_id = ?";

        return $this->getDB()->run($sql, $artifact_id, $program_increment_id);
    }

    public function isLinkedToASprintInMirroredProgramIncrement(int $artifact_id, int $release_tracker_id, int $project_id): bool
    {
        $sql = "SELECT sprint.id
                FROM tracker_changeset_value_artifactlink    AS art_link
                 INNER JOIN tracker_changeset_value          AS cv             ON (cv.id = art_link.changeset_value_id)
                 INNER JOIN tracker_artifact                 AS sprint         ON (sprint.last_changeset_id = cv.changeset_id)
                 INNER JOIN tracker                          AS sprint_tracker ON sprint.tracker_id = sprint_tracker.id
                 INNER JOIN tracker_field                    AS sprint_field   ON (sprint_field.tracker_id = sprint_tracker.id AND sprint_field.formElement_type = 'art_link' AND sprint_field.use_it = 1)
                 INNER JOIN plugin_agiledashboard_planning   AS planning       ON planning.group_id = sprint_tracker.group_id AND sprint.tracker_id = planning.planning_tracker_id
                WHERE art_link.artifact_id = ?
                    AND planning_tracker_id != ?
                    AND sprint_tracker.group_id = ?";

        $rows = $this->getDB()->run($sql, $artifact_id, $release_tracker_id, $project_id);

        return count($rows) > 0;
    }

    public function getUserStoriesOfMirroredProgramIncrementThatAreNotLinkedToASprint(
        MirroredProgramIncrementIdentifier $mirrored_program_increment,
    ): array {
        $sql = <<<SQL
            SELECT user_story.id, user_story_tracker.group_id AS project_id
                FROM tracker_artifact AS mirrored_program_increment
                -- retrieve the artifact_links of milestone
                    INNER JOIN tracker_field                        AS milestone_field    ON (milestone_field.tracker_id = mirrored_program_increment.tracker_id AND milestone_field.formElement_type = 'art_link' AND milestone_field.use_it = 1)
                    INNER JOIN tracker_changeset_value              AS milestone_cv       ON (milestone_cv.changeset_id = mirrored_program_increment.last_changeset_id AND milestone_cv.field_id = milestone_field.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS milestone_artlink  ON (milestone_artlink.changeset_value_id = milestone_cv.id)
                    INNER JOIN tracker_artifact                     AS user_story         ON (user_story.id = milestone_artlink.artifact_id)
                    INNER JOIN tracker                              AS user_story_tracker ON (user_story_tracker.id = user_story.tracker_id)
                -- get planning of mirrored milestone
                    INNER JOIN plugin_agiledashboard_planning       AS planning           ON mirrored_program_increment.tracker_id = planning.planning_tracker_id
                -- check that user_story has a link with feature
                    LEFT JOIN (
                        tracker_artifact AS feature
                            INNER JOIN tracker_field                        AS feature_field    ON (feature_field.tracker_id = feature.tracker_id AND feature_field.formElement_type = 'art_link' AND feature_field.use_it = 1)
                            INNER JOIN tracker_changeset_value              AS feature_cv       ON (feature_cv.changeset_id = feature.last_changeset_id AND feature_cv.field_id = feature_field.id)
                            INNER JOIN tracker_changeset_value_artifactlink AS feature_artlink  ON (feature_artlink.changeset_value_id = feature_cv.id)
                            INNER JOIN plugin_program_management_plan       AS plan             ON feature.tracker_id = plan.plannable_tracker_id
                        ) ON (user_story.id = feature_artlink.artifact_id)
                WHERE mirrored_program_increment.id  = ?
                    AND user_story_tracker.deletion_date IS NULL
                    AND feature.id IS NOT NULL
                -- check user_story is not planned in sprint
                    AND user_story.id NOT IN (
                        SELECT user_story_in_sprint.id
                        FROM tracker_artifact AS sprint
                            INNER JOIN tracker_field                        AS sprint_field    ON (sprint_field.tracker_id = sprint.tracker_id AND sprint_field.formElement_type = 'art_link' AND sprint_field.use_it = 1)
                            INNER JOIN tracker_changeset_value              AS sprint_cv       ON (sprint_cv.changeset_id = sprint.last_changeset_id AND sprint_cv.field_id = sprint_field.id)
                            INNER JOIN tracker_changeset_value_artifactlink AS sprint_artlink  ON (sprint_artlink.changeset_value_id = sprint_cv.id)
                            INNER JOIN tracker_artifact                     AS user_story_in_sprint           ON (user_story_in_sprint.id = sprint_artlink.artifact_id)
                            INNER JOIN tracker                              AS user_story_in_sprint_tracker   ON (user_story_in_sprint_tracker.id = user_story_in_sprint.tracker_id)
                            INNER JOIN plugin_agiledashboard_planning       AS user_story_in_sprint_planning             ON sprint.tracker_id = user_story_in_sprint_planning.planning_tracker_id
                        WHERE
                            user_story_in_sprint.id = user_story.id
                            AND sprint.id != mirrored_program_increment.id
                            AND user_story_in_sprint_tracker.group_id = user_story_tracker.group_id
                            AND user_story_in_sprint_tracker.deletion_date IS NULL
                    )
        SQL;

        $rows = $this->getDB()->run($sql, $mirrored_program_increment->getId());
        return array_map((static fn(array $row): int => $row['id']), $rows);
    }

    /**
     * @psalm-return array{user_story_id:int, project_id:int}[]
     */
    public function getPlannedUserStory(int $artifact_id): array
    {
        $sql = "SELECT feature_artlink.artifact_id AS user_story_id, user_story_tracker.group_id AS project_id FROM
                tracker_artifact AS feature
                    INNER JOIN tracker_field                        AS feature_field      ON (feature_field.tracker_id = feature.tracker_id AND feature_field.formElement_type = 'art_link' AND feature_field.use_it = 1)
                    INNER JOIN tracker_changeset_value              AS feature_cv         ON (feature_cv.changeset_id = feature.last_changeset_id AND feature_cv.field_id = feature_field.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS feature_artlink    ON (feature_artlink.changeset_value_id = feature_cv.id)
                    INNER JOIN plugin_program_management_plan       AS plan               ON feature.tracker_id = plan.plannable_tracker_id
                    INNER JOIN tracker_artifact                     AS user_story         ON (user_story.id = feature_artlink.artifact_id)
                    INNER JOIN tracker                              AS user_story_tracker ON (user_story_tracker.id = user_story.tracker_id)
            WHERE feature.id  = ?";

        return $this->getDB()->run($sql, $artifact_id);
    }

    public function getChildrenOfFeatureInTeamProjects(FeatureIdentifier $feature): array
    {
        $sql = <<<SQL
        SELECT feature_artlink.artifact_id AS children_id
        FROM tracker_artifact AS feature
                INNER JOIN tracker                                 AS feature_tracker    ON feature_tracker.id = feature.tracker_id
                INNER JOIN tracker_field                           AS feature_field      ON (feature_field.tracker_id = feature.tracker_id AND feature_field.formElement_type = 'art_link' AND feature_field.use_it = 1)
                INNER JOIN tracker_changeset_value                 AS feature_cv         ON (feature_cv.changeset_id = feature.last_changeset_id AND feature_cv.field_id = feature_field.id)
                INNER JOIN tracker_changeset_value_artifactlink    AS feature_artlink    ON feature_artlink.changeset_value_id = feature_cv.id
                INNER JOIN plugin_program_management_plan          AS plan               ON feature.tracker_id = plan.plannable_tracker_id
                INNER JOIN tracker_artifact                        AS user_story         ON user_story.id = feature_artlink.artifact_id
                INNER JOIN tracker                                 AS user_story_tracker ON user_story_tracker.id = user_story.tracker_id
                INNER JOIN plugin_program_management_team_projects AS team_project       ON (user_story_tracker.group_id = team_project.team_project_id AND team_project.program_project_id = feature_tracker.group_id)
        WHERE feature.id = ? AND feature_artlink.nature = ?
        SQL;

        $rows = $this->getDB()->run(
            $sql,
            $feature->getId(),
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD
        );

        return array_map(static fn(array $row): int => $row['children_id'], $rows);
    }

    public function getParentOfUserStory(UserStoryIdentifier $story_identifier): ?int
    {
        $sql = "SELECT feature.id AS id
            FROM tracker_artifact AS feature
                     INNER JOIN tracker_field                        AS feature_field          ON (feature_field.tracker_id = feature.tracker_id AND feature_field.formElement_type = 'art_link' AND use_it = 1)
                     INNER JOIN tracker_changeset_value              AS feature_cv         ON (feature_cv.changeset_id = feature.last_changeset_id AND feature_cv.field_id = feature_field.id)
                     INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = feature_cv.id)
                     INNER JOIN tracker_artifact                     AS user_story ON (user_story.id = artlink.artifact_id)
            WHERE user_story.id  = ?
              AND artlink.nature = ?
            ORDER BY feature.id
            ";

        $feature_id = $this->getDB()->single($sql, [
            $story_identifier->getId(),
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD,
        ]);

        if (! $feature_id) {
            return null;
        }

        return $feature_id;
    }

    /**
     * @return int[]
     */
    public function getArtifactsLinkedInChangeset(ChangesetIdentifier $changeset_identifier): array
    {
        $sql = '
            SELECT tcva.artifact_id as artifact_id
            FROM tracker_changeset_value
                INNER JOIN tracker_changeset_value_artifactlink AS tcva on tracker_changeset_value.id = tcva.changeset_value_id
            WHERE changeset_id = ?
        ';

        $rows = $this->getDB()->run($sql, $changeset_identifier->getId());

        return array_map(static fn(array $row): int => $row['artifact_id'], $rows);
    }
}
