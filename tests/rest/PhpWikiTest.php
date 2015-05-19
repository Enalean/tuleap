<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group PhpWikiTests
 */
class PhpWikiTest extends RestBase {
    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSId() {
        $response = $this->getResponse($this->client->options('wiki/'.TestDataBuilder::PHPWIKI_PAGE_ID));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETId() {
        $response = $this->getResponse($this->client->get('wiki/'.TestDataBuilder::PHPWIKI_PAGE_ID));

        $this->assertEquals($response->getStatusCode(), 200);

        $content = $response->json();
        $this->assertArrayHasKey('versions', $content);
        $this->assertCount(4, $content['versions']);
        $this->assertEquals(4, $content['last_version']);
        $this->assertEquals($content['id'], TestDataBuilder::PHPWIKI_PAGE_ID);
        $this->assertEquals($content['versions'][0]['uri'], 'wiki/'.TestDataBuilder::PHPWIKI_PAGE_ID.'/versions?id=1');
    }
}
