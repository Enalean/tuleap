<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

require_once dirname(__FILE__).'/../lib/autoload.php';

use Tuleap\REST\MilestoneBase;

/**
 * @group MilestonesTest
 */
class MilestonesBacklogPatchTest extends MilestoneBase
{

    /** @var Test_Rest_TrackerFactory */
    private $tracker_test_helper;

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

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function setUp()
    {
        parent::setUp();

        $project = $this->getProject(REST_TestDataBuilder::PROJECT_BACKLOG_DND);

        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            $this->client,
            $this->rest_request,
            $project['id'],
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->createReleaseAndBacklog();
    }

    public function testPatchBacklogAfter() {
        $response = $this->getResponse($this->client->patch($this->uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($this->story_mul['id'], $this->story_div['id']),
                'direction'   => 'after',
                'compared_to' => $this->story_add['id']
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->story_add['id'],
                $this->story_mul['id'],
                $this->story_div['id'],
                $this->story_sub['id'],
            ),
            $this->getIdsOrderedByPriority($this->uri)
        );
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPatchBacklogWithoutPermission() {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->client->patch($this->uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($this->story_div['id'], $this->story_mul['id']),
                'direction'   => 'after',
                'compared_to' => $this->story_add['id']
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 403);

        $this->assertEquals(
            array(
                $this->story_add['id'],
                $this->story_sub['id'],
                $this->story_mul['id'],
                $this->story_div['id'],
            ),
            $this->getIdsOrderedByPriority($this->uri)
        );
    }

    public function testPatchBacklogBefore() {
        $response = $this->getResponse($this->client->patch($this->uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($this->story_mul['id'], $this->story_sub['id']),
                'direction'   => 'before',
                'compared_to' => $this->story_add['id']
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->story_mul['id'],
                $this->story_sub['id'],
                $this->story_add['id'],
                $this->story_div['id'],
            ),
            $this->getIdsOrderedByPriority($this->uri)
        );
    }

    public function testPatchBacklogWithItemNotInBacklogRaiseErrors() {
        $exception_thrown = false;

        try {
            $this->getResponse($this->client->patch($this->uri, null, json_encode(array(
                'order' => array(
                    'ids'         => array($this->story_mul['id'], $this->story_sub['id']),
                    'direction'   => 'before',
                    'compared_to' => 1
                )
            ))));
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $exception) {
            $exception_thrown = true;
            $this->assertEquals(409, $exception->getResponse()->getStatusCode());
        }
        $this->assertTrue($exception_thrown, "An exception should have been thrown");
    }

    public function testPatchContentBefore() {
        $uri = 'milestones/'.$this->release['id'].'/content';

        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($this->epic_basic['id'], $this->epic_log['id']),
                'direction'   => 'before',
                'compared_to' => $this->epic_fin['id']
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->epic_adv['id'],
                $this->epic_exp['id'],
                $this->epic_basic['id'],
                $this->epic_log['id'],
                $this->epic_fin['id'],
            ),
            $this->getIdsOrderedByPriority($uri)
        );
    }

    public function testPatchContentAfter() {
        $uri = 'milestones/'.$this->release['id'].'/content';

        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($this->epic_exp['id'], $this->epic_adv['id']),
                'direction'   => 'after',
                'compared_to' => $this->epic_log['id']
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->epic_basic['id'],
                $this->epic_log['id'],
                $this->epic_exp['id'],
                $this->epic_adv['id'],
                $this->epic_fin['id'],
            ),
            $this->getIdsOrderedByPriority($uri)
        );
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPatchContentWithoutPermission() {
        $uri = 'milestones/'.$this->release['id'].'/content';

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($this->epic_adv['id'], $this->epic_exp['id']),
                'direction'   => 'after',
                'compared_to' => $this->epic_log['id']
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 403);

        $this->assertEquals(
            array(
                $this->epic_basic['id'],
                $this->epic_log['id'],
                $this->epic_exp['id'],
                $this->epic_adv['id'],
                $this->epic_fin['id'],
            ),
            $this->getIdsOrderedByPriority($uri)
        );
    }

    public function testPatchContentReMove() {
        $uri = 'milestones/'.$this->release['id'].'/content';

        $another_release = $this->createRelease("Another release", "Current", array());
        $another_release_uri = 'milestones/'.$another_release['id'].'/content';

        $response = $this->getResponse($this->client->patch($another_release_uri, null, json_encode(array(
            'add' => array(
                array(
                    'id'          => $this->epic_log['id'],
                    'remove_from' => $this->release['id'],
                ),
                array(
                    'id'          => $this->epic_adv['id'],
                    'remove_from' => $this->release['id'],
                )
            ),
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->epic_basic['id'],
                $this->epic_exp['id'],
                $this->epic_fin['id'],
            ),
            $this->getIdsOrderedByPriority($uri)
        );

        $another_release_content = $this->getIdsOrderedByPriority($another_release_uri);
        $this->assertCount(2, $another_release_content);
        $this->assertContains($this->epic_log['id'], $another_release_content);
        $this->assertContains($this->epic_adv['id'], $another_release_content);
    }

    public function testPatchAddAndOrder() {
        $uri = 'milestones/'.$this->release['id'].'/content';

        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order'  => array(
                'ids'         => array($this->epic_adv['id'], $this->epic_sta['id']),
                'direction'   => 'after',
                'compared_to' => $this->epic_basic['id']
            ),
            'add' => array(
                array(
                    'id' => $this->epic_sta['id'],
                )
            ),
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->epic_basic['id'],
                $this->epic_adv['id'],
                $this->epic_sta['id'],
                $this->epic_log['id'],
                $this->epic_exp['id'],
                $this->epic_fin['id'],
            ),
            $this->getIdsOrderedByPriority($uri)
        );
    }

    public function testPatchBacklogAddAndOrder() {
        $uri = 'milestones/'.$this->release['id'].'/backlog';

        $inconsistent_story = $this->createStory("Created in sprint");
        $sprint_id = $this->createSprint("Sprint 9001", array($inconsistent_story['id']));

        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order'  => array(
                'ids'         => array($inconsistent_story['id'], $this->story_div['id'], $this->story_sub['id']),
                'direction'   => 'after',
                'compared_to' => $this->story_mul['id']
            ),
            'add' => array(
                array(
                    'id'          => $inconsistent_story['id'],
                    'remove_from' => $sprint_id,
                )
            ),
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->story_add['id'],
                $this->story_mul['id'],
                $inconsistent_story['id'],
                $this->story_div['id'],
                $this->story_sub['id'],
            ),
            $this->getIdsOrderedByPriority($uri)
        );

        $this->assertCount(0, $this->getResponse($this->client->get('milestones/'.$sprint_id.'/backlog'))->json());
    }

    private function getIdsOrderedByPriority($uri) {
        $response = $this->getResponse($this->client->get($uri));
        $actual_order = array();
        foreach($response->json() as $backlog_element) {
            $actual_order[] = $backlog_element['id'];
        }
        return $actual_order;
    }

    private function getProject($shortname) {
        foreach ($this->getResponse($this->client->get('projects'))->json() as $project) {
            if ($project['shortname'] == $shortname) {
                return $project;
            }
        }
        throw new Exception("Project $shortname not found");
    }

    private function createReleaseAndBacklog() {
        $this->release = $this->createRelease(
            "Release 2014 12 02",
            "Current",
            array_merge($this->createReleaseBacklog(), array($this->createSprint("Sprint 10")))
        );
        $this->uri     = 'milestones/'.$this->release['id'].'/backlog';
    }

    private function createRelease($name, $status, array $links) {
        $tracker = $this->tracker_test_helper->getTrackerRest('rel');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Name', $name),
                $tracker->getSubmitListValue('Status', $status),
                $tracker->getSubmitArtifactLinkValue($links)
            )
        );
    }

    private function createReleaseBacklog() {
        $this->story_add = $this->createStory("add two integers");
        $this->story_sub = $this->createStory("sub two integers");
        $this->story_mul = $this->createStory("mul two integers");
        $this->story_div = $this->createStory("div two integers");

        $this->epic_basic = $this->createEpic(
            'Basic calculator',
            array(
                $this->story_add['id'],
                $this->story_sub['id'],
                $this->story_mul['id'],
                $this->story_div['id']
            )
        );

        $this->epic_adv = $this->createEpic('Advanced calculator', array());
        $this->epic_log = $this->createEpic('Logarithm calculator', array());
        $this->epic_exp = $this->createEpic('Expo calculator', array());
        $this->epic_fin = $this->createEpic('Finance calculator', array());
        $this->epic_sta = $this->createEpic('Stats calculator', array());

        return array(
            $this->epic_basic['id'],
            $this->epic_adv['id'],
            $this->epic_log['id'],
            $this->epic_exp['id'],
            $this->epic_fin['id'],
        );
    }

    private function createStory($i_want_to) {
        $tracker = $this->tracker_test_helper->getTrackerRest('story');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('I want to', $i_want_to),
                $tracker->getSubmitListValue('Status', 'To be done')
            )
        );
    }

    private function createEpic($name, array $stories) {
        $tracker = $this->tracker_test_helper->getTrackerRest('epic');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Summary', $name),
                $tracker->getSubmitListValue('Status', 'Open'),
                $tracker->getSubmitArtifactLinkValue($stories)
            )
        );
    }

    private function createSprint($name, array $links = array()) {
        $tracker = $this->tracker_test_helper->getTrackerRest('sprint');
        $sprint  = $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Name', $name),
                $tracker->getSubmitListValue('Status', 'Current'),
                $tracker->getSubmitArtifactLinkValue($links)
            )
        );
        return $sprint['id'];
    }
}
