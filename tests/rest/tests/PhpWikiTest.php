<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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
 *
 */

/**
 * @group PhpWikiTests
 */
class PhpWikiTest extends RestBase {

    public function testOPTIONSId() {
        $response = $this->getResponse($this->client->options('phpwiki/'.REST_TestDataBuilder::PHPWIKI_PAGE_ID));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETId() {
        $response = $this->getResponse($this->client->get('phpwiki/'.REST_TestDataBuilder::PHPWIKI_PAGE_ID));

        $this->assertEquals($response->getStatusCode(), 200);

        $content = $response->json();
        $this->assertArrayHasKey('versions', $content);
        $this->assertCount(4, $content['versions']);
        $this->assertEquals(4, $content['last_version']);
        $this->assertEquals($content['id'], REST_TestDataBuilder::PHPWIKI_PAGE_ID);
        $this->assertEquals($content['versions'][0]['uri'], 'phpwiki/'.REST_TestDataBuilder::PHPWIKI_PAGE_ID.'/versions?version_id=1');
    }

    public function testOPTIONSVersions() {
        $response = $this->getResponse($this->client->options('phpwiki/'.REST_TestDataBuilder::PHPWIKI_PAGE_ID .'/versions'));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGETVersionsReturns400IfNoVersionGiven() {
        $response = $this->getResponse($this->client->get('phpwiki/'.REST_TestDataBuilder::PHPWIKI_PAGE_ID . '/versions'));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGETLastVersion() {
        $response = $this->getResponse($this->client->get('phpwiki/'.REST_TestDataBuilder::PHPWIKI_PAGE_ID . '/versions?version_id=0'));
        $this->assertResponseBodyIsVersion4($response);
    }

    public function testGETVersion4() {
        $response = $this->getResponse($this->client->get('phpwiki/'.REST_TestDataBuilder::PHPWIKI_PAGE_ID . '/versions?version_id=4'));
        $this->assertResponseBodyIsVersion4($response);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGETVersionsThrows404WhenVersionNotExists() {
        $response = $this->getResponse($this->client->get('phpwiki/'.REST_TestDataBuilder::PHPWIKI_PAGE_ID . '/versions?version_id=10'));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    private function assertResponseBodyIsVersion4($response) {
        $json    = $response->json();
        $content = $json[0];

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertArrayHasKey('version_id', $content);
        $this->assertArrayHasKey('wiki_content', $content);
        $this->assertArrayHasKey('formatted_content', $content);

        $formatted_content = file_get_contents(__DIR__.'/../_fixtures/phpwiki/formatted-content-version-4.txt');
        $wiki_content      = file_get_contents(__DIR__.'/../_fixtures/phpwiki/wiki-content-version-4.txt');

        $this->assertEquals(4, $content['version_id']);
        $this->assertEquals($wiki_content, $content['wiki_content']);
        $this->assertEquals($formatted_content, $content['formatted_content']);
    }
}
