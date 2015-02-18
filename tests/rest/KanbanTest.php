<?php
/**
 * Copyright (c) Enalean, 2014-2015. All rights reserved
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
 * @group KanbanTests
 */
class KanbanTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSKanban() {
        $response = $this->getResponse($this->client->options('kanban'));
        $this->assertEquals(array('OPTIONS'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETKanban() {
        $response = $this->getResponse($this->client->get('kanban/'. TestDataBuilder::KANBAN_ID));
        $kanban = $response->json();

        $this->assertEquals(0, $kanban['nb_open']);
        $this->assertEquals(0, $kanban['nb_closed']);
        $this->assertEquals('My first kanban', $kanban['label']);
        $this->assertEquals(TestDataBuilder::KANBAN_TRACKER_ID, $kanban['tracker_id']);
    }

    public function testGETItems() {
        $url = 'kanban/'. TestDataBuilder::KANBAN_ID .'/items?column_id='. TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse($this->client->get($url))->json();

        $this->assertEquals(1, $response['total_size']);
        $this->assertEquals('Doing something', $response['collection'][0]['label']);
    }

    public function testGETArchive() {
        $url = 'kanban/'. TestDataBuilder::KANBAN_ID .'/archive';

        $response = $this->getResponse($this->client->get($url))->json();

        $this->assertEquals(1, $response['total_size']);
        $this->assertEquals('Something archived', $response['collection'][0]['label']);
    }
}
