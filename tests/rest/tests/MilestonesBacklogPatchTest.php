<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('MilestonesTest')]
class MilestonesBacklogPatchTest extends MilestoneBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /** @var Test\Rest\Tracker\Tracker */
    private $release;
    private $story_add;
    private $story_sub;
    private $story_mul;
    private $story_div;
    private $uri;
    private $epic_basic;
    private $epic_adv;
    private $epic_log;
    private $epic_exp;
    private $epic_fin;
    private $epic_sta;
    private $stories;
    private $epics;
    private $releases;
    private $sprints;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->stories         = $this->getArtifactIdsIndexedByTitle('dragndrop', 'story');
        $this->story_add['id'] = $this->stories['add two integers'];
        $this->story_sub['id'] = $this->stories['sub two integers'];
        $this->story_mul['id'] = $this->stories['mul two integers'];
        $this->story_div['id'] = $this->stories['div two integers'];

        $this->epics            = $this->getArtifactIdsIndexedByTitle('dragndrop', 'epic');
        $this->epic_basic['id'] = $this->epics['Basic calculator'];
        $this->epic_adv['id']   = $this->epics['Advanced calculator'];
        $this->epic_log['id']   = $this->epics['Logarithm calculator'];
        $this->epic_exp['id']   = $this->epics['Expo calculator'];
        $this->epic_fin['id']   = $this->epics['Finance calculator'];
        $this->epic_sta['id']   = $this->epics['Stats calculator'];

        $this->releases      = $this->getArtifactIdsIndexedByTitle('dragndrop', 'rel');
        $this->release['id'] = $this->releases['Release 2014 12 02'];

        $this->sprints = $this->getArtifactIdsIndexedByTitle('dragndrop', 'sprint');

        $this->uri = 'milestones/' . $this->release['id'] . '/backlog';
    }

    public function testPatchBacklogForbiddenForRESTReadOnlyUserNotInvolvedInProject(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $this->uri),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPatchBacklogAfter()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $this->uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$this->story_mul['id'], $this->story_div['id']],
                                'direction' => 'after',
                                'compared_to' => $this->story_add['id'],
                            ],
                        ]
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            [
                $this->story_add['id'],
                $this->story_mul['id'],
                $this->story_div['id'],
                $this->story_sub['id'],
            ],
            $this->getIdsOrderedByPriority($this->uri)
        );
    }

    public function testPatchBacklogWithoutPermission()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('PATCH', $this->uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$this->story_div['id'], $this->story_mul['id']],
                                'direction' => 'after',
                                'compared_to' => $this->story_add['id'],
                            ],
                        ]
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 403);

        $this->assertEqualsCanonicalizing(
            [
                $this->story_add['id'],
                $this->story_sub['id'],
                $this->story_mul['id'],
                $this->story_div['id'],
            ],
            $this->getIdsOrderedByPriority($this->uri)
        );
    }

    public function testPatchBacklogBefore()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $this->uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$this->story_mul['id'], $this->story_sub['id']],
                                'direction' => 'before',
                                'compared_to' => $this->story_add['id'],
                            ],
                        ]
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            [
                $this->story_mul['id'],
                $this->story_sub['id'],
                $this->story_add['id'],
                $this->story_div['id'],
            ],
            $this->getIdsOrderedByPriority($this->uri)
        );
    }

    public function testPatchBacklogWithItemNotInBacklogRaiseErrors()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $this->uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$this->story_mul['id'], $this->story_sub['id']],
                                'direction' => 'before',
                                'compared_to' => 1,
                            ],
                        ]
                    )
                )
            )
        );
        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testPatchContentBefore()
    {
        $uri = 'milestones/' . $this->release['id'] . '/content';

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$this->epic_basic['id'], $this->epic_log['id']],
                                'direction' => 'before',
                                'compared_to' => $this->epic_fin['id'],
                            ],
                        ]
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            [
                $this->epic_adv['id'],
                $this->epic_exp['id'],
                $this->epic_basic['id'],
                $this->epic_log['id'],
                $this->epic_fin['id'],
            ],
            $this->getIdsOrderedByPriority($uri)
        );
    }

    public function testPatchContentAfter()
    {
        $uri = 'milestones/' . $this->release['id'] . '/content';

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$this->epic_exp['id'], $this->epic_adv['id']],
                                'direction' => 'after',
                                'compared_to' => $this->epic_log['id'],
                            ],
                        ]
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            [
                $this->epic_basic['id'],
                $this->epic_log['id'],
                $this->epic_exp['id'],
                $this->epic_adv['id'],
                $this->epic_fin['id'],
            ],
            $this->getIdsOrderedByPriority($uri)
        );
    }

    public function testPatchContentWithoutPermission()
    {
        $uri = 'milestones/' . $this->release['id'] . '/content';

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('PATCH', $uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$this->epic_adv['id'], $this->epic_exp['id']],
                                'direction' => 'after',
                                'compared_to' => $this->epic_log['id'],
                            ],
                        ]
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 403);

        $this->assertEquals(
            [
                $this->epic_basic['id'],
                $this->epic_log['id'],
                $this->epic_exp['id'],
                $this->epic_adv['id'],
                $this->epic_fin['id'],
            ],
            $this->getIdsOrderedByPriority($uri)
        );
    }

    public function testPatchContentReMove()
    {
        $uri = 'milestones/' . $this->release['id'] . '/content';

        $another_release_id  = $this->releases['Another release'];
        $another_release_uri = 'milestones/' . $another_release_id . '/content';

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $another_release_uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'add' => [
                                [
                                    'id' => $this->epic_log['id'],
                                    'remove_from' => $this->release['id'],
                                ],
                                [
                                    'id' => $this->epic_adv['id'],
                                    'remove_from' => $this->release['id'],
                                ],
                            ],
                        ]
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            [
                $this->epic_basic['id'],
                $this->epic_exp['id'],
                $this->epic_fin['id'],
            ],
            $this->getIdsOrderedByPriority($uri)
        );

        $another_release_content = $this->getIdsOrderedByPriority($another_release_uri);
        $this->assertCount(2, $another_release_content);
        $this->assertContains($this->epic_log['id'], $another_release_content);
        $this->assertContains($this->epic_adv['id'], $another_release_content);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPatchContentReMove')]
    public function testPatchAddAndOrder()
    {
        $uri = 'milestones/' . $this->release['id'] . '/content';

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$this->epic_fin['id'], $this->epic_sta['id']],
                                'direction' => 'after',
                                'compared_to' => $this->epic_basic['id'],
                            ],
                            'add' => [
                                [
                                    'id' => $this->epic_sta['id'],
                                ],
                            ],
                        ]
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            [
                $this->epic_basic['id'],
                $this->epic_fin['id'],
                $this->epic_sta['id'],
                $this->epic_exp['id'],
            ],
            $this->getIdsOrderedByPriority($uri)
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPatchBacklogBefore')]
    public function testPatchBacklogAddAndOrder()
    {
        $inconsistent_story['id'] = $this->stories['Created in sprint'];
        $sprint_id                = $this->sprints['Sprint 9001'];

        $patch_body = json_encode([
            'order'  => [
                'ids'         => [$inconsistent_story['id'], $this->story_div['id'], $this->story_sub['id']],
                'direction'   => 'after',
                'compared_to' => $this->story_mul['id'],
            ],
            'add' => [
                [
                    'id'          => $inconsistent_story['id'],
                    'remove_from' => $sprint_id,
                ],
            ],
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', $this->uri)->withBody($this->stream_factory->createStream($patch_body)));
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            [
                $this->story_mul['id'],
                $inconsistent_story['id'],
                $this->story_div['id'],
                $this->story_sub['id'],
                $this->story_add['id'],
            ],
            $this->getIdsOrderedByPriority($this->uri)
        );

        $response = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $sprint_id . '/backlog'));
        $this->assertCount(0, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function getIdsOrderedByPriority($uri)
    {
        $response     = $this->getResponse($this->request_factory->createRequest('GET', $uri));
        $actual_order = [];
        foreach (json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR) as $backlog_element) {
            $actual_order[] = $backlog_element['id'];
        }
        return $actual_order;
    }
}
