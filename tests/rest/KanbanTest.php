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
        $response = $this->getResponse($this->client->get('kanban/'. TestDataBuilder::KANBAN_TRACKER_ID));
        $kanban = $response->json();

        $this->assertEquals(0, $kanban['nb_open']);
        $this->assertEquals(0, $kanban['nb_closed']);
        $this->assertEquals('My first kanban', $kanban['label']);
    }
}
