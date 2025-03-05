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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectResourceTest extends \RestBase
{
    public function testOPTIONS(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->getProgramProjectId() . '/program_plan'),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testPUTEmptyTeam(): void
    {
        $program_id = $this->getProgramProjectId();

        $team_definition = json_encode(['team_ids' => []]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'projects/' . $program_id . '/program_teams')->withBody($this->stream_factory->createStream($team_definition))
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPUTTeam(): void
    {
        $program_id = $this->getProgramProjectId();
        $team_id    = $this->getTeamProjectId();

        $team_definition = json_encode(['team_ids' => [$team_id]]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'projects/' . $program_id . '/program_teams')->withBody($this->stream_factory->createStream($team_definition)),
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
                'program_increment_tracker_id' => $this->tracker_ids[$project_id]['pi'],
                'plannable_tracker_ids' => [$this->tracker_ids[$project_id]['bug'],$this->tracker_ids[$project_id]['features']],
                'permissions' => ['can_prioritize_features' => ["${project_id}_4"]],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'projects/' . $project_id . '/program_plan')->withBody($this->stream_factory->createStream($plan_definition)),
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
                'program_increment_tracker_id' => $this->tracker_ids[$project_id]['pi'],
                'plannable_tracker_ids' => [$this->tracker_ids[$project_id]['bug'],$this->tracker_ids[$project_id]['features']],
                'permissions' => ['can_prioritize_features' => ["${project_id}_4"]],
                'custom_label' => 'Custom Program Increments',
                'custom_sub_label' => 'program increment',
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'projects/' . $project_id . '/program_plan')->withBody($this->stream_factory->createStream($plan_definition)),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTTeam
     */
    public function testPUTPlanWithIteration(): void
    {
        $project_id = $this->getProgramProjectId();

        $plan_definition = json_encode(
            [
                'program_increment_tracker_id' => $this->tracker_ids[$project_id]['pi'],
                'plannable_tracker_ids' => [$this->tracker_ids[$project_id]['bug'],$this->tracker_ids[$project_id]['features']],
                'permissions' => ['can_prioritize_features' => ["${project_id}_4"]],
                'iteration' => ['iteration_tracker_id' => $this->tracker_ids[$project_id]['iteration']],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'projects/' . $project_id . '/program_plan')->withBody($this->stream_factory->createStream($plan_definition)),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTTeam
     */
    public function testPUTPlanWithIterationAndCustomLabel(): void
    {
        $project_id = $this->getProgramProjectId();

        $plan_definition = json_encode(
            [
                'program_increment_tracker_id' => $this->tracker_ids[$project_id]['pi'],
                'plannable_tracker_ids' => [$this->tracker_ids[$project_id]['bug'],$this->tracker_ids[$project_id]['features']],
                'permissions' => ['can_prioritize_features' => ["${project_id}_4"]],
                'iteration' => ['iteration_tracker_id' => $this->tracker_ids[$project_id]['iteration'], 'iteration_label' => 'My Iterations', 'iteration_sub_label' => 'iteration'],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'projects/' . $project_id . '/program_plan')->withBody($this->stream_factory->createStream($plan_definition)),
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
            $this->request_factory->createRequest('GET', 'projects/' . urlencode((string) $project_id) . '/program_increments')
        );

        self::assertEquals(200, $response->getStatusCode());
        $program_increments = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $program_increments);
        self::assertEquals('Program Increment at the top', $program_increments[0]['title']);
        self::assertEquals('Planned', $program_increments[0]['status']);
        self::assertEquals('PI', $program_increments[1]['title']);
        self::assertEquals('In development', $program_increments[1]['status']);

        return $program_increments[1]['id'];
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testGetIterations(int $program_increment_id): int
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'program_increment/' . urlencode((string) $program_increment_id) . '/iterations')
        );

        $iteration = [
            'title' => 'iteration',
            'status' => 'On Going',
            'start_date' => '2021-06-14T00:00:00+02:00',
            'end_date' => '2021-07-01T00:00:00+02:00',
            'user_can_update' => true,
        ];

        self::assertEquals(200, $response->getStatusCode());
        $iterations = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $iterations);
        $received_iteration = $iterations[0];
        $iteration_id       = $received_iteration['id'];
        unset($received_iteration['id'], $received_iteration['uri'], $received_iteration['xref']);
        self::assertEquals($iteration, $received_iteration);

        return $iteration_id;
    }

    /**
     * @depends testGetIterations
     */
    public function testGetIterationContent(int $iteration_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'iteration/' . urlencode((string) $iteration_id) . '/content')
        );

        self::assertEquals(200, $response->getStatusCode());
        $user_stories = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $user_stories);
        self::assertEquals('US1', $user_stories[0]['title']);
        self::assertEquals('User Stories', $user_stories[0]['tracker']['label']);
        self::assertEquals('team', $user_stories[0]['project']['label']);
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testGetProgramIncrementContent(int $id): void
    {
        $this->checkGetFirstElementOfProgramIncrement($id, 'title', 'My other artifact for top backlog manipulation');
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testPatchBacklogWithFeatureRemovedFromProgramIncrementWithBooleanParameterSetToTrue(int $program_increment_id): void
    {
        $project_id        = $this->getProgramProjectId();
        $program_increment = $this->getArtifactWithArtifactLink('release_number', 'PI', $project_id, 'pi');
        $bug_id            = $this->getBugIDWithSpecificSummary('My artifact for top backlog manipulation', $project_id);

        // Remove elements from backlog
        $this->updateArtifactLinks($program_increment_id, [], $program_increment['artifact_link_id']);

        // Check backlog and program increment are empty
        self::assertEmpty($this->getTopBacklogContent($project_id));
        $this->checkGetEmptyProgramIncrementContent($program_increment_id);

        // Add bug in program increment
        $this->updateArtifactLinks($program_increment_id, [['id' => $bug_id]], $program_increment['artifact_link_id']);
        $this->checkGetFirstElementOfProgramIncrement($program_increment_id, 'id', (string) $bug_id);

        // Remove bug from program increment and add it in program backlog because
        // parameter `remove_from_program_increment_to_add_to_the_backlog` is true
        $this->patchTopBacklog($project_id, [$bug_id], [], true);
        $this->checkGetEmptyProgramIncrementContent($program_increment_id);

        // Check bug is moved to backlog
        $backlog_content = $this->getTopBacklogContent($project_id);
        self::assertCount(1, $backlog_content);
        self::assertEquals($bug_id, $backlog_content[0]);

        // Remove elements from backlog
        $this->patchTopBacklog($project_id, [], [$bug_id]);
        self::assertEmpty($this->getTopBacklogContent($project_id));
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testPatchBacklogCannotMoveFeatureFromProgramIncrementToBacklogBecauseBooleanParameterIsNotSetToTrue(int $program_increment_id): void
    {
        $project_id        = $this->getProgramProjectId();
        $program_increment = $this->getArtifactWithArtifactLink('release_number', 'PI', $project_id, 'pi');
        $bug_id            = $this->getBugIDWithSpecificSummary('My artifact for top backlog manipulation', $project_id);

        // Remove elements from backlog
        $this->updateArtifactLinks($program_increment_id, [], $program_increment['artifact_link_id']);

        // Check backlog and program increment are empty
        self::assertEmpty($this->getTopBacklogContent($project_id));
        $this->checkGetEmptyProgramIncrementContent($program_increment_id);

        // Add bug in program increment
        $this->updateArtifactLinks($program_increment_id, [['id' => $bug_id]], $program_increment['artifact_link_id']);
        $this->checkGetFirstElementOfProgramIncrement($program_increment_id, 'id', (string) $bug_id);

        // Bug is not removed from program increment because
        // parameter `remove_from_program_increment_to_add_to_the_backlog` is false
        $this->patchTopBacklog($project_id, [$bug_id], [], false);
        $this->checkGetFirstElementOfProgramIncrement($program_increment_id, 'id', (string) $bug_id);
        self::assertEmpty($this->getTopBacklogContent($project_id));
    }

    /**
     * @depends testPUTTeam
     */
    public function testCannotUnplannedFeatureWithLinkedPlannedStoryInTeam(): int
    {
        $project_id        = $this->getProgramProjectId();
        $program_increment = $this->getArtifactWithArtifactLink('release_number', 'PI', $project_id, 'pi');
        $featureA          = $this->getArtifactWithArtifactLink('description', 'FeatureA', $project_id, 'features');


        $team_id     = $this->getTeamProjectId();
        $user_story1 = $this->getArtifactWithArtifactLink('i_want_to', 'US1', $team_id, 'story');
        $sprint      = $this->getArtifactWithArtifactLink('sprint_name', 'S1', $team_id, 'sprint');

        // Plan US in sprint
        $this->updateArtifactLinks($sprint['id'], [['id' => $user_story1['id']]], $sprint['artifact_link_id']);

        // Plan features in program increment
        $this->updateArtifactLinks(
            $program_increment['id'],
            [['id' => $featureA['id']]],
            $program_increment['artifact_link_id']
        );

        // Check program increment has features
        $this->checkGetFirstElementOfProgramIncrement($program_increment['id'], 'id', (string) $featureA['id']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'projects/' . urlencode((string) $project_id) . '/program_backlog')->withBody($this->stream_factory->createStream(json_encode(
                $this->formatPatchTopBacklogParameters([$featureA['id']], [], true, null),
                JSON_THROW_ON_ERROR
            )))
        );
        self::assertEquals(400, $response->getStatusCode());
        self::assertStringContainsString('The feature with id #' . $featureA['id'] . ' cannot be unplanned because some linked user stories are planned in Teams project.', $response->getBody()->getContents());

        // Check program increment has still feature with planned US
        $this->checkGetFirstElementOfProgramIncrement($program_increment['id'], 'id', (string) $featureA['id']);

        return $program_increment['id'];
    }

    /**
     * @depends testCannotUnplannedFeatureWithLinkedPlannedStoryInTeam
     */
    public function testGetProgramIncrementBacklog(int $program_increment_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'program_increment/' . urlencode((string) $program_increment_id) . '/backlog')
        );

        self::assertEquals(200, $response->getStatusCode());
        $user_stories = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $user_stories);
        self::assertEquals('US1', $user_stories[0]['title']);
        self::assertEquals('User Stories', $user_stories[0]['tracker']['label']);
        self::assertEquals('team', $user_stories[0]['project']['label']);
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testPatchBacklogWithFeatureRemovedFromProgramAndOrderInBacklog(int $program_increment_id): void
    {
        $project_id        = $this->getProgramProjectId();
        $program_increment = $this->getArtifactWithArtifactLink('release_number', 'PI', $project_id, 'pi');
        $bug_id_1          = $this->getBugIDWithSpecificSummary('My artifact for top backlog manipulation', $project_id);
        $bug_id_2          = $this->getBugIDWithSpecificSummary('My other artifact for top backlog manipulation', $project_id);

        $this->updateArtifactLinks($program_increment_id, [], $program_increment['artifact_link_id']);
        $this->patchTopBacklog($project_id, [$bug_id_1], []);

        // Check backlog has one item and program increment element is empty
        self::assertCount(1, $this->getTopBacklogContent($project_id));
        $this->checkGetEmptyProgramIncrementContent($program_increment_id);

        // Add bug_2 in program increment
        $this->updateArtifactLinks($program_increment_id, [['id' => $bug_id_2]], $program_increment['artifact_link_id']);

        $this->checkGetFirstElementOfProgramIncrement($program_increment_id, 'title', 'My other artifact for top backlog manipulation');
        $this->checkGetFirstElementOfProgramIncrement($program_increment_id, 'id', (string) $bug_id_2);

        // Remove bug from program increment and add it in program backlog after bug_1
        $this->patchTopBacklog(
            $project_id,
            [$bug_id_2],
            [],
            true,
            ['ids' => [$bug_id_2], 'direction' => 'after', 'compared_to' => $bug_id_1]
        );

        $this->checkGetEmptyProgramIncrementContent($program_increment_id);

        $backlog_content = $this->getTopBacklogContent($project_id);

        self::assertCount(2, $backlog_content);
        self::assertEquals($bug_id_1, $backlog_content[0]);
        self::assertEquals($bug_id_2, $backlog_content[1]);

        // Move bug_2 before bug_1
        $this->patchTopBacklog(
            $project_id,
            [],
            [],
            false,
            ['ids' => [$bug_id_1], 'direction' => 'after', 'compared_to' => $bug_id_2]
        );

        $backlog_content = $this->getTopBacklogContent($project_id);

        self::assertCount(2, $backlog_content);
        self::assertEquals($bug_id_2, $backlog_content[0]);
        self::assertEquals($bug_id_1, $backlog_content[1]);

        // Clear program backlog
        $this->patchTopBacklog($project_id, [], [$bug_id_1]);
        $this->patchTopBacklog($project_id, [], [$bug_id_2]);
        self::assertEmpty($this->getTopBacklogContent($project_id));
    }

    /**
     * @depends testManipulateFeature
     */
    public function testGetProgramBacklogChildren(): void
    {
        $project_id = $this->getProgramProjectId();
        $featureA   = $this->getArtifactWithArtifactLink('description', 'FeatureA', $project_id, 'features');
        $response   = $this->getResponse(
            $this->request_factory->createRequest('GET', 'program_backlog_items/' . urlencode((string) $featureA['id']) . '/children')
        );

        self::assertEquals(200, $response->getStatusCode());
        $program_increments = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
     * @depends testGetProgramIncrementBacklog
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


        // Plan $user_story1 in $featureA
        $this->updateParentArtifact(
            $user_story1['id'],
            $featureA['id'],
            $user_story1['artifact_link_id']
        );

        // Plan $user_story2 in $featureB
        $this->updateParentArtifact(
            $user_story2['id'],
            $featureB['id'],
            $user_story2['artifact_link_id']
        );

        // plan featureA in program increment
        $this->updateArtifactLinks(
            $program_increment['id'],
            [['id' => $featureA['id']]],
            $program_increment['artifact_link_id']
        );

        // check in team project that the two US stories are present in top backlog
        $this->checkLinksArePresentInReleaseTopBacklog($release_mirror['id'], [$user_story1['id'], $user_story2['id']]);

        /*
         * Setup explained:
         * User stories 1 and 2 are respectively children of featureA and featureB
         * Feature A is the only feature planned in an iteration at program level
         * Feature B is still in the program top-backlog
         * User stories 1 and 2 are both in the backlog of a release inside a mirrored PI of the team project
         */

        // remove featureA from the program increment
        $this->updateArtifactLinks($program_increment['id'], [], $program_increment['artifact_link_id']);

        // US1 should have been removed from the team backlog, US2 should be still present
        $this->checkLinksArePresentInReleaseTopBacklog($release_mirror['id'], [$user_story2['id']]);
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testManipulatePIContent(int $program_increment_id): void
    {
        $program_id = $this->getProgramProjectId();
        $featureB   = $this->getArtifactWithArtifactLink('description', 'FeatureB', $program_id, 'features');

        $this->patchProgramIncrementContent($program_increment_id, $featureB['id'], null);

        $this->checkGetFirstElementOfProgramIncrement($program_increment_id, 'id', (string) $featureB['id']);
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testUserCannotRemoveFeatureFromPIWhenItHasChildrenPlannedInMilestones(int $program_increment_id): void
    {
        $team_id        = $this->getTeamProjectId();
        $program_id     = $this->getProgramProjectId();
        $featureB       = $this->getArtifactWithArtifactLink('description', 'FeatureB', $program_id, 'features');
        $release_mirror = $this->getArtifactWithArtifactLink('release_number', 'PI', $team_id, 'rel');
        $user_story2    = $this->getArtifactWithArtifactLink('i_want_to', 'US2', $team_id, 'story');
        $sprint         = $this->getArtifactWithArtifactLink('sprint_name', 'S1', $team_id, 'sprint');

        // Set featureB parent of user story 2
        $this->updateParentArtifact(
            $user_story2['id'],
            $featureB['id'],
            $user_story2['artifact_link_id']
        );

        // link sprint as a child of mirrored release
        $this->linkSprintToRelease($release_mirror['id'], $sprint['id']);

        // link user story 2 to a Sprint in Team Project
        $this->updateArtifactLinks($sprint['id'], [['id' => $user_story2['id']]], $sprint['artifact_link_id']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'program_increment/' . urlencode((string) $program_increment_id) . '/content')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode(
                            [
                                'add' => [$featureB['id']],
                                'order' => null,
                                'remove_from_program_increment_to_add_to_the_backlog' => true,
                            ],
                            JSON_THROW_ON_ERROR
                        )
                    )
                )
        );
        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testReorderFeatureInPIContent(int $program_increment_id): void
    {
        $program_id        = $this->getProgramProjectId();
        $bug_id_1          = $this->getBugIDWithSpecificSummary('My artifact for top backlog manipulation', $program_id);
        $bug_id_2          = $this->getBugIDWithSpecificSummary('My other artifact for top backlog manipulation', $program_id);
        $program_increment = $this->getArtifactWithArtifactLink('release_number', 'PI', $program_id, 'pi');


        $this->updateArtifactLinks($program_increment_id, [['id' => $bug_id_1], ['id' => $bug_id_2]], $program_increment['artifact_link_id']);

        $this->patchProgramIncrementContent($program_increment_id, null, ['ids' => [$bug_id_2], 'direction' => 'after', 'compared_to' => $bug_id_1]);

        // Check featureB have been moved after featureA
        $this->checkGetElementNumberNOfProgramIncrement($program_increment_id, 0, 'id', (string) $bug_id_1);
        $this->checkGetElementNumberNOfProgramIncrement($program_increment_id, 1, 'id', (string) $bug_id_2);

        $this->patchProgramIncrementContent($program_increment_id, null, ['ids' => [$bug_id_2], 'direction' => 'before', 'compared_to' => $bug_id_1]);

        // Check feature have been reordered
        $this->checkGetElementNumberNOfProgramIncrement($program_increment_id, 0, 'id', (string) $bug_id_2);
        $this->checkGetElementNumberNOfProgramIncrement($program_increment_id, 1, 'id', (string) $bug_id_1);
    }

    private function checkGetEmptyProgramIncrementContent(int $program_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'program_increment/' . urlencode((string) $program_id) . '/content')
        );

        self::assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEmpty($content);
    }

    private function checkGetFirstElementOfProgramIncrement(int $program_id, string $key, string $artifact_title): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'program_increment/' . urlencode((string) $program_id) . '/content')
        );

        self::assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $content);
        self::assertEquals($artifact_title, $content[0][$key]);
    }

    private function checkGetElementNumberNOfProgramIncrement(int $program_id, int $number_feature, string $key, string $artifact_title): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'program_increment/' . urlencode((string) $program_id) . '/content')
        );

        self::assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($artifact_title, $content[$number_feature][$key]);
    }

    private function getBugIDWithSpecificSummary(string $summary, int $program_id): int
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'trackers/' . urlencode((string) $this->tracker_ids[$program_id]['bug']) . '/artifacts?&expert_query=' . urlencode('summary="' . $summary . '"'))
        );

        self::assertEquals(200, $response->getStatusCode());

        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $artifacts);
        self::assertTrue(isset($artifacts[0]['id']));

        return $artifacts[0]['id'];
    }

    private function getArtifactWithArtifactLink(
        string $field_name,
        string $field_value,
        int $project_id,
        string $tracker_name,
    ): array {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'trackers/' . urlencode((string) $this->tracker_ids[$project_id][$tracker_name]) .
            '/artifacts/?&values=all&expert_query=' . urlencode($field_name . '="' . $field_value . '"'))
        );

        self::assertEquals(200, $response->getStatusCode());

        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $artifacts);
        self::assertTrue(isset($artifacts[0]['id']));

        return [
            'id'               => $artifacts[0]['id'],
            'artifact_link_id' => $this->getArtifactLinkFieldId($artifacts[0]['values']),
        ];
    }

    /**
     * @return int[]
     */
    private function getTopBacklogContent(int $program_id): array
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . urlencode((string) $program_id) . '/program_backlog')
        );

        self::assertEquals(200, $response->getStatusCode());

        $top_backlog_elements    = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $top_backlog_element_ids = [];

        foreach ($top_backlog_elements as $top_backlog_element) {
            $top_backlog_element_ids[] = $top_backlog_element['id'];
        }

        return $top_backlog_element_ids;
    }

    /**
     * @param int[] $to_add
     * @param int[] $to_remove
     * @psalm-param null|array{ids: int[], direction: string, compared_to: int} $order
     * @throws \JsonException
     */
    private function patchTopBacklog(
        int $program_id,
        array $to_add,
        array $to_remove,
        bool $remove_program_increment_link = false,
        ?array $order = null,
    ): void {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'projects/' . urlencode((string) $program_id) . '/program_backlog')->withBody($this->stream_factory->createStream(json_encode(
                $this->formatPatchTopBacklogParameters($to_add, $to_remove, $remove_program_increment_link, $order),
                JSON_THROW_ON_ERROR
            )))
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @psalm-param null|array{ids: int[], direction: string, compared_to: int} $order
     */
    private function patchProgramIncrementContent(int $program_increment_id, ?int $to_add, ?array $order): void
    {
        $feature_to_add = [];
        if ($to_add) {
            $feature_to_add = [['id' => $to_add]];
        }
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'program_increment/' . urlencode((string) $program_increment_id) . '/content')->withBody($this->stream_factory->createStream(json_encode(['add' => $feature_to_add, 'order' => $order], JSON_THROW_ON_ERROR)))
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @param int[] $to_add
     * @param int[] $to_remove
     * @psalm-param null|array{ids: int[], direction: string, compared_to: int} $order
     * @return array<{add: {id: int, from_remove: ?int}[], remove: {id: int}[], order: ?{ids: int[], direction: string, compared_to: int}}>
     */
    private function formatPatchTopBacklogParameters(
        array $to_add,
        array $to_remove,
        bool $remove_program_increment_link,
        ?array $order,
    ): array {
        if ($order) {
            return [
                'add'    => self::formatTopBacklogElementChange($to_add),
                'remove' => self::formatTopBacklogElementChange($to_remove),
                'remove_from_program_increment_to_add_to_the_backlog' => $remove_program_increment_link,
                'order'  => $order,
            ];
        }

        return [
            'add'    => self::formatTopBacklogElementChange($to_add),
            'remove' => self::formatTopBacklogElementChange($to_remove),
            'remove_from_program_increment_to_add_to_the_backlog' => $remove_program_increment_link,
        ];
    }

    private function linkSprintToRelease(int $release_id, int $sprint_id): void
    {
        $values   = ['add'  => [['id' => $sprint_id]]];
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'milestones/' . urlencode((string) $release_id) . '/milestones')->withBody($this->stream_factory->createStream(json_encode($values, JSON_THROW_ON_ERROR)))
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    private function updateArtifactLinks(int $artifact_id, array $links, int $artifact_field_id): void
    {
        $values = [
            'values'  => [['field_id' => $artifact_field_id, 'links' => $links]],
            'comment' => ['body' => '', 'format' => 'text'],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artifacts/' . urlencode((string) $artifact_id))->withBody($this->stream_factory->createStream(json_encode($values, JSON_THROW_ON_ERROR)))
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    private function updateParentArtifact(int $artifact_id, int $parent_id, int $artifact_field_id): void
    {
        $values = [
            'values'  => [['field_id' => $artifact_field_id, 'links' => [], 'parent' => ['id' => $parent_id]]],
            'comment' => ['body' => '', 'format' => 'text'],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artifacts/' . urlencode((string) $artifact_id))->withBody($this->stream_factory->createStream(json_encode($values, JSON_THROW_ON_ERROR)))
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
            if ($field['type'] === 'art_link') {
                return (int) $field['field_id'];
            }
        }

        return null;
    }

    private function checkLinksArePresentInReleaseTopBacklog(int $mirror_id, array $user_story_linked): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . urlencode((string) $mirror_id) . '/backlog?limit=50&offset=0')
        );

        self::assertEquals(200, $response->getStatusCode());

        $planned_elements = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);


        $planned_elements_id = [];
        foreach ($planned_elements as $element) {
            $planned_elements_id[] = $element['id'];
        }

        self::assertEquals([], array_diff($planned_elements_id, $user_story_linked));
    }
}
