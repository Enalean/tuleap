<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * @group MilestoneTests
 */
class MilestoneTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_NAME),
            $request
        );
    }

    public function testOPTIONSContent() {
        $response = $this->getResponse($this->client->options('milestones/1/content'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETContent() {
        $response = $this->getResponse($this->client->get('milestones/1/content'));

        $backlog_items = $response->json();

        $this->assertCount(4, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "First epic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['tracker'], array('id' => '5', 'uri' => 'trackers/5'));
        $this->assertEquals($first_backlog_item['artifact'], array('id' => '2', 'uri' => 'artifacts/2'));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Second epic");
        $this->assertEquals($second_backlog_item['status'], "Closed");
        $this->assertEquals($second_backlog_item['tracker'], array('id' => '5', 'uri' => 'trackers/5'));
        $this->assertEquals($second_backlog_item['artifact'], array('id' => '3', 'uri' => 'artifacts/3'));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Third epic");
        $this->assertEquals($third_backlog_item['status'], "Closed");
        $this->assertEquals($third_backlog_item['tracker'], array('id' => '5', 'uri' => 'trackers/5'));
        $this->assertEquals($third_backlog_item['artifact'], array('id' => '4', 'uri' => 'artifacts/4'));

        $fourth_backlog_item = $backlog_items[3];
        $this->assertArrayHasKey('id', $fourth_backlog_item);
        $this->assertEquals($fourth_backlog_item['label'], "Fourth epic");
        $this->assertEquals($fourth_backlog_item['status'], "Open");
        $this->assertEquals($fourth_backlog_item['tracker'], array('id' => '5', 'uri' => 'trackers/5'));
        $this->assertEquals($fourth_backlog_item['artifact'], array('id' => '5', 'uri' => 'artifacts/5'));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTContent() {
        $response_put = $this->getResponse($this->client->put('milestones/1/content', null, '[2,5]'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/1/content'));
        $backlog_items = $response_get->json();

        $this->assertCount(2, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "First epic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['tracker'], array('id' => '5', 'uri' => 'trackers/5'));
        $this->assertEquals($first_backlog_item['artifact'], array('id' => '2', 'uri' => 'artifacts/2'));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Fourth epic");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['tracker'], array('id' => '5', 'uri' => 'trackers/5'));
        $this->assertEquals($second_backlog_item['artifact'], array('id' => '5', 'uri' => 'artifacts/5'));
    }

    public function testPUTContentOnlyOneElement() {
        $response_put = $this->getResponse($this->client->put('milestones/1/content', null, '[5]'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/1/content'));
        $backlog_items = $response_get->json();

        $this->assertCount(1, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Fourth epic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['tracker'], array('id' => '5', 'uri' => 'trackers/5'));
        $this->assertEquals($first_backlog_item['artifact'], array('id' => '5', 'uri' => 'artifacts/5'));
    }
}
