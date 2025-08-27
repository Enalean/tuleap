<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\REST\MilestoneBase;
use Tuleap\REST\RESTTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('MilestonesTest')]
class MilestonesTest extends MilestoneBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testOPTIONS(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'milestones'));
        self::assertEqualsCanonicalizing(['OPTIONS'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSMilestonesId(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'milestones/' . $this->release_artifact_ids[1]));
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'milestones'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        self::assertEqualsCanonicalizing(['OPTIONS'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'milestones/' . $this->release_artifact_ids[1]),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETResourcesMilestones(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1]));

        $this->assertEquals(200, $response->getStatusCode());

        $milestone = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertGETResourcesMilestones($milestone);
        $this->assertGETResourcesBacklog($milestone);
        $this->assertGETResourcesContent($milestone);
        $this->assertGETResourcesBurndownCardwallEmpty($milestone);
    }

    public function testGETResourcesMilestonesWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1]),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $milestone = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertGETResourcesMilestones($milestone);
        $this->assertGETResourcesBacklog($milestone);
        $this->assertGETResourcesContent($milestone);
        $this->assertGETResourcesBurndownCardwallEmpty($milestone);
    }

    private function assertGETResourcesMilestones(array $milestone): void
    {
        $this->assertEquals(
            [
                'uri'    => 'milestones/' . $this->release_artifact_ids[1] . '/milestones',
                'accept' => [
                    'trackers' => [
                        [
                            'id'  => $this->sprints_tracker_id,
                            'uri' => "trackers/$this->sprints_tracker_id",
                            'label' => 'Sprints',
                            'color' => 'inca-silver',
                            'project' => [
                                'id'    => $this->project_private_member_id,
                                'uri'   => 'projects/' . $this->project_private_member_id,
                                'label' => RESTTestDataBuilder::PROJECT_PRIVATE_MEMBER_LABEL,
                                'icon' => '',
                            ],
                        ],
                    ],
                ],
            ],
            $milestone['resources']['milestones']
        );

        self::assertArrayHasKey('sub_milestone_type', $milestone);
    }

    private function assertGETResourcesBacklog(array $milestone): void
    {
        $this->assertEquals(
            [
                'uri'    => 'milestones/' . $this->release_artifact_ids[1] . '/backlog',
                'accept' => [
                    'trackers' => [
                        [
                            'id'  => $this->user_stories_tracker_id,
                            'uri' => 'trackers/' . $this->user_stories_tracker_id,
                            'label' => 'User Stories',
                            'color' => 'inca-silver',
                            'project' => [
                                'id'    => $this->project_private_member_id,
                                'uri'   => 'projects/' . $this->project_private_member_id,
                                'label' => RESTTestDataBuilder::PROJECT_PRIVATE_MEMBER_LABEL,
                                'icon' => '',
                            ],
                        ],
                    ],
                    'parent_trackers' => [
                        [
                            'id'  => $this->epic_tracker_id,
                            'uri' => 'trackers/' . $this->epic_tracker_id,
                            'label' => 'Epics',
                            'color' => 'inca-silver',
                            'project' => [
                                'id'    => $this->project_private_member_id,
                                'uri'   => 'projects/' . $this->project_private_member_id,
                                'label' => RESTTestDataBuilder::PROJECT_PRIVATE_MEMBER_LABEL,
                                'icon' => '',
                            ],
                        ],
                    ],
                ],
            ],
            $milestone['resources']['backlog']
        );
    }

    private function assertGETResourcesContent(array $milestone): void
    {
        $this->assertEquals(
            [
                'uri'    => 'milestones/' . $this->release_artifact_ids[1] . '/content',
                'accept' => [
                    'trackers' => [
                        [
                            'id'  => $this->epic_tracker_id,
                            'uri' => 'trackers/' . $this->epic_tracker_id,
                            'label' => 'Epics',
                            'color' => 'inca-silver',
                            'project' => [
                                'id'    => $this->project_private_member_id,
                                'uri'   => 'projects/' . $this->project_private_member_id,
                                'label' => RESTTestDataBuilder::PROJECT_PRIVATE_MEMBER_LABEL,
                                'icon' => '',
                            ],
                        ],
                    ],
                ],
            ],
            $milestone['resources']['content']
        );
    }

    private function assertGETResourcesBurndownCardwallEmpty(array $milestone): void
    {
        $this->assertNull(
            $milestone['resources']['cardwall']
        );
        $this->assertNull(
            $milestone['resources']['burndown']
        );
    }

    public function testGETResourcesBurndown(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->sprint_artifact_ids[1]));
        $this->assertEquals(200, $response->getStatusCode());

        $milestone = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'uri' => 'milestones/' . $this->sprint_artifact_ids[1] . '/burndown',
            ],
            $milestone['resources']['burndown']
        );
    }

    public function testGETResourcesCardwall(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->sprint_artifact_ids[1]));
        $this->assertEquals(200, $response->getStatusCode());

        $milestone = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'uri'    => 'milestones/' . $this->sprint_artifact_ids[1] . '/cardwall',
            ],
            $milestone['resources']['cardwall']
        );
    }
}
