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

/**
 * @group MilestonesTest
 */
class MilestonesBacklogPatchTest extends RestBase {

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
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function setUp() {
        parent::setUp();

        $project = $this->getProject(TestDataBuilder::PROJECT_BACKLOG_DND);

        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            $this->client,
            $this->rest_request,
            $project['id'],
            TestDataBuilder::TEST_USER_1_NAME
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

    public function testPatchContentRemove() {
        $uri = 'milestones/'.$this->release['id'].'/content';

        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'remove' => array(
                $this->epic_log['id'],
                $this->epic_adv['id'],
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
                $this->epic_sta['id'],
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

        $story_in_the_wild = $this->createStory("Created in sprint");
        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order'  => array(
                'ids'         => array($story_in_the_wild['id'], $this->story_div['id'], $this->story_sub['id']),
                'direction'   => 'after',
                'compared_to' => $this->story_mul['id']
            ),
            'add' => array(
                $story_in_the_wild['id'],
            ),
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->story_add['id'],
                $this->story_mul['id'],
                $story_in_the_wild['id'],
                $this->story_div['id'],
                $this->story_sub['id'],
            ),
            $this->getIdsOrderedByPriority($uri)
        );
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
        $this->release = $this->createRelease();
        $this->uri     = 'milestones/'.$this->release['id'].'/backlog';
    }

    private function createRelease() {
        $tracker = $this->tracker_test_helper->getTrackerRest('rel');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Name', 'Release 2014 12 02'),
                $tracker->getSubmitListValue('Status', 'Current'),
                $tracker->getSubmitArtifactLinkValue(array_merge($this->createReleaseBacklog(), array($this->createSprint("Sprint 10"))))
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
