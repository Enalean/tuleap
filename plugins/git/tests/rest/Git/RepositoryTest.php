<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All rights reserved
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
use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;
use Tuleap\Git\REST\TestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group GitTests
 */
class RepositoryTest extends TestBase
{

    protected function getResponseForNonMember($request)
    {
        return $this->getResponse($request, REST_TestDataBuilder::TEST_USER_2_NAME);
    }

    public function testGetGitRepository(): void
    {
        $response = $this->getResponse($this->client->get(
            'git/' . GitDataBuilder::REPOSITORY_GIT_ID
        ));

        $this->assertGETGitRepository($response);
    }

    public function testGetGitRepositoryWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get(
                'git/' . GitDataBuilder::REPOSITORY_GIT_ID
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETGitRepository($response);
    }

    private function assertGETGitRepository(Response $response): void
    {
        $repository = $response->json();

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals($repository['description'], 'Git repository');
        $this->assertArrayHasKey('server', $repository);
    }

    public function testOPTIONS(): void
    {
        $response = $this->getResponse($this->client->options('git/' . GitDataBuilder::REPOSITORY_GIT_ID));
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('git/' . GitDataBuilder::REPOSITORY_GIT_ID),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGetGitRepositoryThrows403IfUserCantSeeRepository(): void
    {
        $response = $this->getResponseForNonMember($this->client->get(
            'git/' . GitDataBuilder::REPOSITORY_GIT_ID
        ));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testPATCHGitRepositoryWithReadOnlySiteAdmin(): void
    {
        $patch_payload = json_encode(
            array(
                "migrate_to_gerrit" => array(
                    "server" => 1,
                    "permissions" => "default"
                )
            )
        );

        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID;

        $response = $this->getResponse(
            $this->client->patch(
                $url,
                null,
                $patch_payload
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testOPTIONSFiles(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref'          => 'master'
        ]);

        $response = $this->getResponse($this->client->options($url));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSFilesWithReadOnlyAdmin(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
                'path_to_file' => 'file01',
                'ref'          => 'master'
            ]);

        $response = $this->getResponse(
            $this->client->options($url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETFiles(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
                'path_to_file' => 'file01',
                'ref' => 'master'
            ]);

        $response = $this->getResponse($this->client->get($url));

        $this->assertGETFiles($response);
    }

    public function testGETFilesWithReadOnlyAdmin(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
                'path_to_file' => 'file01',
                'ref' => 'master'
            ]);

        $response = $this->getResponse(
            $this->client->get($url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETFiles($response);
    }

    private function assertGetFiles(Response $response): void
    {
        $content  = $response->json();

        $this->assertEquals($content['name'], 'file01');
        $this->assertEquals($content['path'], 'file01');
        $this->assertNotEmpty($content['content']);
    }

    public function testGETFilesOnOtherBranchThanMaster(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file02',
            'ref'          => 'branch_file_02'
        ]);

        $response = $this->getResponse($this->client->get($url));
        $content  = $response->json();

        $this->assertEquals($content['name'], 'file02');
        $this->assertEquals($content['path'], 'file02');
        $this->assertNotEmpty($content['content']);
    }

    public function testGETFilesOnNonExistingFile(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'NotAFile',
            'ref'          => 'master'
        ]);

        $response  = $this->getResponse($this->client->get($url));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testGETFilesOnNonExistingBranch(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref'          => 'NotABranch'
        ]);

        $response  = $this->getResponse($this->client->get($url));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testOPTIONSBranches(): void
    {
        $response = $this->getResponse($this->client->options('git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSBranchesWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETBranches(): void
    {
        $response = $this->getResponse($this->client->get(
            'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches'
        ));

        $this->assertGETBranches($response);
    }

    public function testGETBranchesWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETBranches($response);
    }

    private function assertGETBranches(Response $response): void
    {
        $branches = $response->json();

        $this->assertCount(2, $branches);
        $this->assertEqualsCanonicalizing(
            $branches,
            [
                [
                    'name' => 'master',
                    'commit' => [
                        'html_url'       => '/plugins/git/test-git/repo01?a=commit&h=8957aa17cf3f56658d91d1c67f60e738f3fdcb3e',
                        'id'             => '8957aa17cf3f56658d91d1c67f60e738f3fdcb3e',
                        'title'          => '04',
                        'message'        => '04',
                        'author_name'    => 'Test User 1',
                        'author_email'   => 'test_user_1@example.com',
                        'authored_date'  => '2018-09-05T11:12:07+02:00',
                        'committed_date' => '2018-09-05T11:12:07+02:00',
                        'author'         => [
                            'id'           => 102,
                            'uri'          => 'users/102',
                            'user_url'     => '/users/rest_api_tester_1',
                            'real_name'    => 'Test User 1',
                            'display_name' => 'Test User 1 (rest_api_tester_1)',
                            'username'     => 'rest_api_tester_1',
                            'ldap_id'      => 'tester1',
                            'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                            'is_anonymous' => false,
                            'has_avatar'   => false
                        ],
                        'commit_status' => null,
                        'verification'  => ['signature' => null]
                    ],
                ],
                [
                    'name' => 'branch_file_02',
                    'commit' => [
                        'html_url'       => '/plugins/git/test-git/repo01?a=commit&h=bcbc8956071c646493d484c64a6034b663e073e0',
                        'id'             => 'bcbc8956071c646493d484c64a6034b663e073e0',
                        'title'          => '03',
                        'message'        => '03',
                        'author_name'    => 'Test User 1',
                        'author_email'   => 'test_user_1@example.com',
                        'authored_date'  => '2018-09-05T11:10:39+02:00',
                        'committed_date' => '2018-09-05T11:10:39+02:00',
                        'author'         => [
                            'id'           => 102,
                            'uri'          => 'users/102',
                            'user_url'     => '/users/rest_api_tester_1',
                            'real_name'    => 'Test User 1',
                            'display_name' => 'Test User 1 (rest_api_tester_1)',
                            'username'     => 'rest_api_tester_1',
                            'ldap_id'      => 'tester1',
                            'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                            'is_anonymous' => false,
                            'has_avatar'   => false
                        ],
                        'commit_status' => null,
                        'verification'  => ['signature' => null]
                    ]
                ],
            ]
        );
    }

    public function testOPTIONSTags(): void
    {
        $response = $this->getResponse($this->client->options('git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tags'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSTagsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tags'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETTags(): void
    {
        $response = $this->getResponse($this->client->get(
            'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tags'
        ));

        $this->assertGETTags($response);
    }

    public function testGETTagsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tags'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETTags($response);
    }

    public function testPOSTGitWithReadOnlyAdmin(): void
    {
        $project_id   = $this->getProjectId(GitDataBuilder::PROJECT_TEST_GIT_SHORTNAME);
        $post_payload = json_encode(
            array(
                'project_id' => $project_id,
                'name' => 'newTestGitRepository'
            )
        );
        $response = $this->getResponse(
            $this->client->post('git/', null, $post_payload),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPOSTGit(): void
    {
        $project_id   = $this->getProjectId(GitDataBuilder::PROJECT_TEST_GIT_SHORTNAME);
        $post_payload = json_encode(
            array(
                'project_id' => $project_id,
                'name' => 'newTestGitRepository'
            )
        );
        $response = $this->getResponse(
            $this->client->post('git/', null, $post_payload)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testPOSTStatusWithReadOnlyAdmin(): void
    {
        $post_payload = json_encode(
            array(
                "state" => "success",
                "token" => "someToken"
            )
        );

        $response = $this->getResponse(
            $this->client->post('git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/statuses/5d408503daf6f1348e264122cfa8fc89a30f7f12', null, $post_payload),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    private function assertGETTags(Response $response): void
    {
        $tags = $response->json();
        $this->assertCount(1, $tags);
        $this->assertEqualsCanonicalizing(
            $tags,
            [
                [
                    'name' => 'v0',
                    'commit' => [
                        'html_url'       => '/plugins/git/test-git/repo01?a=commit&h=5d408503daf6f1348e264122cfa8fc89a30f7f12',
                        'id'             => '5d408503daf6f1348e264122cfa8fc89a30f7f12',
                        'title'          => 'First commit',
                        'message'        => 'First commit',
                        'author_name'    => 'Test User 1',
                        'authored_date'  => '2018-09-05T11:05:05+02:00',
                        'committed_date' => '2018-09-05T11:05:05+02:00',
                        'author_email'   => 'test_user_1@example.com',
                        'author'         => [
                            'id'           => 102,
                            'uri'          => 'users/102',
                            'user_url'     => '/users/rest_api_tester_1',
                            'real_name'    => 'Test User 1',
                            'display_name' => 'Test User 1 (rest_api_tester_1)',
                            'username'     => 'rest_api_tester_1',
                            'ldap_id'      => 'tester1',
                            'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                            'is_anonymous' => false,
                            'has_avatar'   => false
                        ],
                        'commit_status' => null,
                        'verification'  => ['signature' => null]
                    ]
                ]
            ]
        );
    }

    public function testGETPullRequestsWithReadOnlyAdmin(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/pull_requests';

        $response = $this->getResponse(
            $this->client->get($url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertGETPullRequests($response);
    }

    public function testGETPullRequests(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/pull_requests';

        $response = $this->getResponse($this->client->get($url));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertGETPullRequests($response);
    }

    private function assertGETPullRequests(Response $response): void
    {
        $content = $response->json();

        $this->assertEquals(2, count($content['collection']));
        $this->assertEquals(2, $content['total_size']);
    }
}
