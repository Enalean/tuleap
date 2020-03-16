<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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

use Guzzle\Http\Message\Response;

/**
 * @group BacklogItemsTest
 */
class BacklogItemsTest extends RestBase  //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private $tasks;
    private $stories;

    private $stories_ids = array();

    public function setUp() : void
    {
        parent::setUp();
        $this->tasks   = $this->getArtifactIdsIndexedByTitle('private-member', 'task');
        $this->stories = $this->getArtifactIdsIndexedByTitle('private-member', 'story');

        $this->stories_ids = [
            $this->stories['build a new interface'],
            $this->stories['finish the story'],
            $this->stories['end of the story'],
        ];
    }

    public function testOPTIONS()
    {
        $response = $this->getResponse($this->client->options('backlog_items/' . $this->stories_ids[0]));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSWithUserRESTReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->client->options('backlog_items/' . $this->stories_ids[0]),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertSame(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGET()
    {
        $response = $this->getResponse($this->client->get('backlog_items/' . $this->stories_ids[0]));

        $this->assertGET($response);
    }

    public function testGETWithUserRESTReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->client->get('backlog_items/' . $this->stories_ids[0]),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGET($response);
    }

    private function assertGET(Response $response)
    {
        $backlog_item  = $response->json();

        $this->assertEquals("build a new interface", $backlog_item['label']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOPTIONSChildren()
    {
        $response = $this->getResponse($this->client->options('backlog_items/' . $this->stories_ids[0] . '/children'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSChildrenWithUserRESTReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->client->options('backlog_items/' . $this->stories_ids[0] . '/children'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertSame(['OPTIONS', 'GET', 'PATCH'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETChildren()
    {
        $response      = $this->getResponse($this->client->get('backlog_items/' . $this->stories_ids[0] . '/children'));
        $backlog_items = $response->json();
        $this->assertCount(0, $backlog_items);

        $response = $this->getResponse($this->client->get('backlog_items/' . $this->stories_ids[1] . '/children'));

        $this->assertGETChildren($response);
    }

    public function testGETChildrenWithUserRESTReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->client->get('backlog_items/' . $this->stories_ids[0] . '/children'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $backlog_items = $response->json();
        $this->assertCount(0, $backlog_items);

        $response = $this->getResponse(
            $this->client->get('backlog_items/' . $this->stories_ids[1] . '/children'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETChildren($response);
    }

    private function assertGETChildren(Response $response)
    {
        $backlog_items = $response->json();
        $this->assertCount(2, $backlog_items);

        $first_task = $backlog_items[0];
        $this->assertEquals("Implement the feature", $first_task['label']);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPATCHChildrenWithUserRESTReadOnlyAdmin()
    {
        $uri      = 'backlog_items/' . $this->stories_ids[1] . '/children';
        $response = $this->getResponse(
            $this->client->get($uri),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $backlog_items = $response->json();
        $first_task    = $backlog_items[0];

        $this->assertEquals("Implement the feature", $first_task['label']);

        $first_id  = $backlog_items[0]['id'];
        $second_id = $backlog_items[1]['id'];

        $patch_body = json_encode(array(
            'order' => array(
                'ids'         => array($second_id),
                'direction'   => 'before',
                'compared_to' => $first_id
            )
        ));

        // invert order of the two tasks
        $response = $this->getResponse(
            $this->client->patch($uri, null, $patch_body),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPATCHChildren()
    {
        $uri = 'backlog_items/' . $this->stories_ids[1] . '/children';
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        $first_task = $backlog_items[0];
        $this->assertEquals($first_task['label'], "Implement the feature");

        $first_id  = $backlog_items[0]['id'];
        $second_id = $backlog_items[1]['id'];

        // invert order of the two tasks
        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($second_id),
                'direction'   => 'before',
                'compared_to' => $first_id
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        // assert that the two tasks are in the order
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        $first_task = $backlog_items[0];
        $this->assertEquals($first_task['label'], "Write tests");

        $first_id  = $backlog_items[0]['id'];
        $second_id = $backlog_items[1]['id'];

        // re-invert order of the two tasks
        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($first_id),
                'direction'   => 'after',
                'compared_to' => $second_id
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        // assert that the two tasks are in the order
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        $first_task = $backlog_items[0];
        $this->assertEquals($first_task['label'], "Implement the feature");
    }

    public function testPATCHChildrenDuplicateIds()
    {
        $uri = 'backlog_items/' . $this->stories_ids[1] . '/children';
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        $first_task = $backlog_items[0];
        $this->assertEquals($first_task['label'], "Implement the feature");

        $first_id  = $backlog_items[0]['id'];
        $second_id = $backlog_items[1]['id'];

        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($second_id, $second_id),
                'direction'   => 'before',
                'compared_to' => $first_id
            )
        ))));

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testPATCHSomeoneElseChildren()
    {
        $uri = 'backlog_items/' . $this->stories_ids[1] . '/children';
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        foreach ($backlog_items as $backlog_item) {
            $this->assertNotEquals($backlog_item['id'], 9999);
        }

        $first_id  = $backlog_items[0]['id'];

        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array(9999),
                'direction'   => 'before',
                'compared_to' => $first_id
            )
        ))));

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testGETChildrenWithWrongId()
    {
        $response      = $this->getResponse($this->client->get('backlog_items/700/children'));
        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testPatchChildrenAdd()
    {
        $uri = 'backlog_items/' . $this->stories_ids[1] . '/children';
        $backlog_items = $this->getResponse($this->client->get($uri))->json();

        $this->assertCount(2, $backlog_items);
        $first_id  = $backlog_items[0]['id'];
        $second_id = $backlog_items[1]['id'];
        $third_id  = $this->tasks['My loneliness is killing me'];

        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($third_id),
                'direction'   => 'after',
                'compared_to' => $first_id
            ),
            'add' => array(
                array(
                    'id' => $third_id,
                )
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $first_id,
                $third_id,
                $second_id,
            ),
            $this->getIdsOrderedByPriority($uri)
        );
    }

    public function testPatchChildrenMove()
    {
        $uri = 'backlog_items/' . $this->stories_ids[2] . '/children';
        $backlog_items = $this->getResponse($this->client->get($uri))->json();

        $first_id  = $backlog_items[0]['id'];
        $second_id = $backlog_items[1]['id'];

        $task_in_another_story_id = $this->tasks['Bla bla bla'];
        $another_story_id = $this->stories['Another story'];

        try {
            $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($task_in_another_story_id),
                'direction'   => 'after',
                'compared_to' => $first_id
            ),
            'add' => array(
                array(
                    'id'          => $task_in_another_story_id,
                    'remove_from' => $another_story_id,
                )
            )
            ))));
            $this->assertEquals($response->getStatusCode(), 200);
        } catch (Exception $e) {
            $res = $e->getResponse();
            var_dump($res->getStatusCode(), $res->getBody(true));
        }
        $this->assertEquals(
            array(
                $first_id,
                $task_in_another_story_id,
                $second_id,
            ),
            $this->getIdsOrderedByPriority($uri)
        );

        $this->assertCount(0, $this->getResponse($this->client->get('backlog_items/' . $another_story_id . '/children'))->json());
    }

    private function getIdsOrderedByPriority($uri)
    {
        $response = $this->getResponse($this->client->get($uri));
        $actual_order = array();
        foreach ($response->json() as $backlog_element) {
            $actual_order[] = $backlog_element['id'];
        }
        return $actual_order;
    }
}
