<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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

namespace Git;

use GitDataBuilder;
use REST_TestDataBuilder;
use Tuleap\Git\REST\TestBase;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group GitTests
 */
class RepositoryTest extends TestBase {

    protected function getResponseForNonMember($request) {
        return $this->getResponse($request, REST_TestDataBuilder::TEST_USER_2_NAME);
    }

    public function testGetGitRepository() {
        $response  = $this->getResponse($this->client->get(
            'git/'.GitDataBuilder::REPOSITORY_GIT_ID
        ));

        $repository = $response->json();

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals($repository['description'], 'Git repository');
        $this->assertArrayHasKey('server', $repository);
    }

    public function testOPTIONS() {
        $response = $this->getResponse($this->client->options('git/'.GitDataBuilder::REPOSITORY_GIT_ID));
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetGitRepositoryThrows403IfUserCantSeeRepository() {
        $response = $this->getResponseForNonMember($this->client->get(
            'git/'.GitDataBuilder::REPOSITORY_GIT_ID
        ));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testOPTIONSFiles()
    {
        $url = 'git/'.GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref'          => 'master'
        ]);

        $response = $this->getResponse($this->client->options($url));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETFiles()
    {
        $url = 'git/'.GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref'          => 'master'
        ]);

        $response = $this->getResponse($this->client->get($url));
        $content  = $response->json();

        $this->assertEquals($content['name'], 'file01');
        $this->assertEquals($content['path'], 'file01');
        $this->assertNotEmpty($content['content']);
    }

    public function testGETFilesOnOtherBranchThanMaster()
    {
        $url = 'git/'.GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file02',
            'ref'          => 'branch_file_02'
        ]);

        $response = $this->getResponse($this->client->get($url));
        $content  = $response->json();

        $this->assertEquals($content['name'], 'file02');
        $this->assertEquals($content['path'], 'file02');
        $this->assertNotEmpty($content['content']);
    }

    /**
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGETFilesOnNonExistingFile()
    {
        $url = 'git/'.GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'NotAFile',
            'ref'          => 'master'
        ]);

        $response  = $this->getResponse($this->client->get($url));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    /**
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGETFilesOnNonExistingBranch()
    {
        $url = 'git/'.GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref'          => 'NotABranch'
        ]);

        $response  = $this->getResponse($this->client->get($url));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testOPTIONSBranches()
    {
        $response = $this->getResponse($this->client->options('git/'.GitDataBuilder::REPOSITORY_GIT_ID . '/branches'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETBranches() {
        $response  = $this->getResponse($this->client->get(
            'git/'.GitDataBuilder::REPOSITORY_GIT_ID . '/branches'
        ));

        $branches = $response->json();

        $this->assertCount(2, $branches);
        $this->assertEquals(
            $branches,
            [
                [
                    'name' => 'master',
                    'commit' => [
                        'commit_id' => '8957aa17cf3f56658d91d1c67f60e738f3fdcb3e'
                    ]
                ],
                [
                    'name' => 'branch_file_02',
                    'commit' => [
                        'commit_id' => 'bcbc8956071c646493d484c64a6034b663e073e0'
                    ]
                ],
            ],
            $message = '',
            $delta = 0,
            $max_depth = 10,
            $canonicalize = true
        );
    }

    public function testOPTIONSTags()
    {
        $response = $this->getResponse($this->client->options('git/'.GitDataBuilder::REPOSITORY_GIT_ID . '/tags'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETTags() {
        $response  = $this->getResponse($this->client->get(
            'git/'.GitDataBuilder::REPOSITORY_GIT_ID . '/tags'
        ));

        $tags = $response->json();
        $this->assertCount(1, $tags);
        $this->assertEquals(
            $tags,
            [
                [
                    'name' => 'v0',
                    'commit' => [
                        'commit_id' => '504a824c0c91ec6c549ae89df1f42ca86d7700b2'
                    ]
                ]
            ],
            $message = '',
            $delta = 0,
            $max_depth = 10,
            $canonicalize = true
        );
    }
}
