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
 * @group ProjectTests
 */
class ProjectTest extends RestBase {

    public function testGETbyIdForAdmin() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/101'));

        $this->assertEquals($response->json(), array(
            'id'        => '101',
            'uri'       => 'projects/101',
            'label'     => TestDataBuilder::TEST_PROJECT_LONG_NAME,
            'resources' => array(
                'projects/101/plannings'
            ))
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSbyIdForAdmin() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/101'));

        $this->assertEquals(array('GET', 'OPTIONS'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETbyIdForForbiddenUser() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponseByName(TestDataBuilder::TEST_USER_NAME, $this->client->options('projects/100'));
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 403);
            $exception = true;
        }

        $this->assertTrue($exception);
    }
}
?>