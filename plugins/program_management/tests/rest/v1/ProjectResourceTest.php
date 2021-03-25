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

namespace Tuleap\ProgramManagement\REST\v1;

use REST_TestDataBuilder;

class ProjectResourceTest extends \RestBase
{
    public function testOPTIONS(): void
    {
        $response = $this->getResponse(
            $this->client->options('projects/' . $this->getProgramProjectId() . '/program_plan'),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'PUT'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPUTTeam(): void
    {
        $program_id = $this->getProgramProjectId();
        $team_id    = $this->getTeamProjectId();

        $team_definition = json_encode(["team_ids" => [$team_id]]);

        $response = $this->getResponse(
            $this->client->put('projects/' . $program_id . '/program_teams', null, $team_definition),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTTeam
     */
    public function testPUTPlan(): void
    {
        $project_id = $this->getProgramProjectId();

        $plan_definition = json_encode(
            [
                  "program_increment_tracker_id" => $this->tracker_ids[$project_id]['pi'],
                  "plannable_tracker_ids" => [$this->tracker_ids[$project_id]['bug'],$this->tracker_ids[$project_id]['features']],
                  "permissions" => ['can_prioritize_features' => ["${project_id}_4"]],
            ]
        );

        $response = $this->getResponse(
            $this->client->put('projects/' . $project_id . '/program_plan', null, $plan_definition),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTTeam
     */
    public function testPUTPlanWithCustomLabel(): void
    {
        $project_id = $this->getProgramProjectId();

        $plan_definition = json_encode(
            [
                "program_increment_tracker_id" => $this->tracker_ids[$project_id]['pi'],
                "plannable_tracker_ids" => [$this->tracker_ids[$project_id]['bug'],$this->tracker_ids[$project_id]['features']],
                "permissions" => ['can_prioritize_features' => ["${project_id}_4"]],
                "custom_label" => "Custom Program Increments",
                "custom_sub_label" => "program increment"
            ]
        );

        $response = $this->getResponse(
            $this->client->put('projects/' . $project_id . '/program_plan', null, $plan_definition),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTTeam
     */
    public function testGetProgramIncrements(): int
    {
        $project_id = $this->getProgramProjectId();

        $response = $this->getResponse(
            $this->client->get('projects/' . urlencode((string) $project_id) . '/program_increments')
        );

        self::assertEquals(200, $response->getStatusCode());
        $program_increments = $response->json();
        self::assertCount(1, $program_increments);
        self::assertEquals('PI', $program_increments[0]['title']);
        self::assertEquals('In development', $program_increments[0]['status']);

        return $program_increments[0]['id'];
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testGetProgramIncrementContent(int $id): void
    {
        $response = $this->getResponse(
            $this->client->get('program_increment/' . urlencode((string) $id) . '/content')
        );

        self::assertEquals(200, $response->getStatusCode());
        $content = $response->json();

        self::assertGreaterThan(1, $content);
        self::assertEquals('My artifact', $content[0]['artifact_title']);
    }

    /**
     * @depends testManipulateFeature
     */
    public function testGetProgramBacklogChildren(): void
    {
        $project_id = $this->getProgramProjectId();
        $featureA   = $this->getArtifactWithArtifactLink('description', 'FeatureA', $project_id, 'features');
        $response   = $this->getResponse(
            $this->client->get('program_backlog_items/' . urlencode((string) $featureA['id']) . '/children')
        );

        self::assertEquals(200, $response->getStatusCode());
        $program_increments = $response->json();
        self::assertCount(1, $program_increments);
        self::assertEquals('US1', $program_increments[0]['title']);
    }

    /**
     * @depends testPUTTeam
     */
    public function testManipulateTopBacklog(): void
    {
        $project_id = $this->getProgramProjectId();

        $bug_id = $this->getBugIDWithSpecificSummary('My artifact for top backlog manipulation', $project_id);

        $this->patchTopBacklog($project_id, [], [$bug_id]);
        self::assertEmpty($this->getTopBacklogContent($project_id));

        $this->patchTopBacklog($project_id, [$bug_id], []);
        self::assertEquals([$bug_id], $this->getTopBacklogContent($project_id));

        $this->patchTopBacklog($project_id, [], [$bug_id]);
        self::assertEmpty($this->getTopBacklogContent($project_id));
    }

    /**
     * @depends testPUTTeam
     */
    public function testManipulateFeature(): void
    {
        $program_id = $this->getProgramProjectId();
        $team_id    = $this->getTeamProjectId();

        $program_increment = $this->getArtifactWithArtifactLink('release_number', 'PI', $program_id, 'pi');
        $release_mirror    = $this->getArtifactWithArtifactLink('release_number', 'PI', $team_id, 'rel');
        $featureA          = $this->getArtifactWithArtifactLink('description', 'FeatureA', $program_id, 'features');
        $featureB          = $this->getArtifactWithArtifactLink('description', 'FeatureB', $program_id, 'features');
        $user_story1       = $this->getArtifactWithArtifactLink('i_want_to', 'US1', $team_id, 'story');
        $user_story2       = $this->getArtifactWithArtifactLink('i_want_to', 'US2', $team_id, 'story');
        $sprint            = $this->getArtifactWithArtifactLink('sprint_name', 'S1', $team_id, 'sprint');

        // plan the feature in program increment
        $this->updateArtifactLinks(
            $program_increment['id'],
            [['id' => $featureA['id']], ['id' => $featureB['id']]],
            $program_increment['artifact_link_id']
        );

        // check in team project that the two US stories are present in top backlog
        $this->checkLinksArePresentInReleaseTopBacklog($release_mirror['id'], [$user_story1['id'], $user_story2['id']]);

        // link sprint as a child of mirrored release
        $this->linkSprintToRelease($release_mirror['id'], $sprint['id']);

        // link user story 1 to a Sprint in Team Project
        $this->updateArtifactLinks($sprint['id'], [['id' => $user_story1['id']]], $sprint['artifact_link_id']);

        // remove feature in program
        $this->updateArtifactLinks($program_increment['id'], [], $program_increment['artifact_link_id']);

        // US1 is linked in top backlog (linked into sprint), US2 is no longer present
        $this->checkLinksArePresentInReleaseTopBacklog($team_id, [$user_story1['id']]);
    }

    private function getBugIDWithSpecificSummary(string $summary, int $program_id): int
    {
        $response = $this->getResponse(
            $this->client->get('trackers/' . urlencode((string) $this->tracker_ids[$program_id]['bug']) . '/artifacts?&expert_query=' . urlencode('summary="' . $summary . '"'))
        );

        self::assertEquals(200, $response->getStatusCode());

        $artifacts = $response->json();

        self::assertCount(1, $artifacts);
        self::assertTrue(isset($artifacts[0]['id']));

        return $artifacts[0]['id'];
    }

    private function getArtifactWithArtifactLink(
        string $field_name,
        string $field_value,
        int $project_id,
        string $tracker_name
    ): array {
        $response = $this->getResponse(
            $this->client->get(
                'trackers/' . urlencode((string) $this->tracker_ids[$project_id][$tracker_name]) .
                '/artifacts/?&values=all&expert_query=' . urlencode($field_name . '="' . $field_value . '"')
            )
        );

        self::assertEquals(200, $response->getStatusCode());

        $artifacts = $response->json();

        self::assertCount(1, $artifacts);
        self::assertTrue(isset($artifacts[0]['id']));

        return [
            'id'               => $artifacts[0]['id'],
            'artifact_link_id' => $this->getArtifactLinkFieldId($artifacts[0]['values'])
        ];
    }

    /**
     * @return int[]
     */
    private function getTopBacklogContent(int $program_id): array
    {
        $response = $this->getResponse(
            $this->client->get('projects/' . urlencode((string) $program_id) . '/program_backlog')
        );

        self::assertEquals(200, $response->getStatusCode());

        $top_backlog_elements    = $response->json();
        $top_backlog_element_ids = [];

        foreach ($top_backlog_elements as $top_backlog_element) {
            $top_backlog_element_ids[] = $top_backlog_element['artifact_id'];
        }

        return $top_backlog_element_ids;
    }

    /**
     * @param int[] $to_add
     * @param int[] $to_remove
     * @throws \JsonException
     */
    private function patchTopBacklog(int $program_id, array $to_add, array $to_remove): void
    {
        $response = $this->getResponse(
            $this->client->patch(
                'projects/' . urlencode((string) $program_id) . '/program_backlog',
                null,
                json_encode(
                    [
                        'add'    => self::formatTopBacklogElementChange($to_add),
                        'remove' => self::formatTopBacklogElementChange($to_remove)
                    ],
                    JSON_THROW_ON_ERROR
                )
            )
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    private function updateArtifactLinks(int $artifact_id, array $links, int $artifact_field_id): void
    {
        $values = [
            "values"  => [["field_id" => $artifact_field_id, 'links' => $links]],
            "comment" => ["body" => "", "format" => "text"]
        ];

        $response = $this->getResponse(
            $this->client->put(
                'artifacts/' . urlencode((string) $artifact_id),
                null,
                json_encode($values, JSON_THROW_ON_ERROR)
            )
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    private function linkSprintToRelease(int $release_id, int $sprint_id): void
    {
        $values = ["add"  => [["id" => $sprint_id]]];

        $response = $this->getResponse(
            $this->client->patch(
                'milestones/' . urlencode((string) $release_id) . '/milestones',
                null,
                json_encode($values, JSON_THROW_ON_ERROR)
            )
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @param int[] $elements
     * @return array{id: int}[]
     *
     * @psalm-pure
     */
    private static function formatTopBacklogElementChange(array $elements): array
    {
        $formatted_elements = [];

        foreach ($elements as $element) {
            $formatted_elements[] = ['id' => $element];
        }

        return $formatted_elements;
    }

    private function getProgramProjectId(): int
    {
        return $this->getProjectId('program');
    }

    private function getTeamProjectId(): int
    {
        return $this->getProjectId('team');
    }

    private function getArtifactLinkFieldId(array $field_list): ?int
    {
        foreach ($field_list as $field) {
            if ($field['type'] === "art_link") {
                return (int) $field['field_id'];
            }
        }

        return null;
    }

    private function checkLinksArePresentInReleaseTopBacklog(int $mirror_id, array $user_story_linked): void
    {
        $response = $this->getResponse(
            $this->client->get('milestones/' . urlencode((string) $mirror_id) . '/backlog?limit=50&offset=0')
        );

        self::assertEquals(200, $response->getStatusCode());

        $planned_elmenents = $response->json();

        $planned_elements_id = [];
        foreach ($planned_elmenents as $element) {
            $planned_elements_id[] = $element['id'];
        }

        self::assertEquals([], array_diff($planned_elements_id, $user_story_linked));
    }
}
