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

namespace Git;

use GitDataBuilder;
use REST_TestDataBuilder;
use Tuleap\Git\REST\TestBase;

/**
 * @group GitTests
 */
final class RepositoryTest extends TestBase
{
    private string $artifact_reference;
    private string $artifact_url;

    public function setUp(): void
    {
        parent::setUp();
        $artifact_ids_by_title    = $this->getArtifactIdsIndexedByTitle(
            "test-git",
            "tracker_1",
        );
        $this->artifact_reference = "tracker_1 #" . $artifact_ids_by_title["test_artifact_1"];
        $this->artifact_url       = "https://localhost/goto?" . http_build_query(
            [
                "key" => "tracker_1",
                "val" => $artifact_ids_by_title["test_artifact_1"],
                "group_id" => $this->getProjectId("test-git"),
            ]
        );
    }

    protected function getResponseForNonMember($request)
    {
        return $this->getResponse($request, REST_TestDataBuilder::TEST_USER_2_NAME);
    }

    public function testGetGitRepository(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID));

        $this->assertGETGitRepository($response);
    }

    public function testGetGitRepositoryWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETGitRepository($response);
    }

    private function assertGETGitRepository(\Psr\Http\Message\ResponseInterface $response): void
    {
        $repository = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('id', $repository);
        self::assertEquals('repo01', $repository['name']);
        self::assertEquals('Git repository', $repository['description']);
        self::assertArrayHasKey('server', $repository);
        self::assertEquals('master', $repository['default_branch']);
    }

    public function testOPTIONS(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID));
        $this->assertEquals(['OPTIONS', 'GET', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(['OPTIONS', 'GET', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGetGitRepositoryThrows403IfUserCantSeeRepository(): void
    {
        $response = $this->getResponseForNonMember($this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testPATCHGitRepositoryWithReadOnlySiteAdmin(): void
    {
        $patch_payload = json_encode(
            [
                "migrate_to_gerrit" => [
                    "server" => 1,
                    "permissions" => "default",
                ],
            ]
        );

        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID;

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream($patch_payload)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testOPTIONSFiles(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref'          => 'master',
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', $url));
        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSFilesWithReadOnlyAdmin(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref'          => 'master',
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', $url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETFiles(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref' => 'master',
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertGETFiles($response);
    }

    public function testGETFilesWithReadOnlyAdmin(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref' => 'master',
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETFiles($response);
    }

    private function assertGetFiles(\Psr\Http\Message\ResponseInterface $response): void
    {
        $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($content['name'], 'file01');
        $this->assertEquals($content['path'], 'file01');
        $this->assertNotEmpty($content['content']);
    }

    public function testGETFilesOnOtherBranchThanMaster(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file02',
            'ref'          => 'branch_file_02',
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));
        $content  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($content['name'], 'file02');
        $this->assertEquals($content['path'], 'file02');
        $this->assertNotEmpty($content['content']);
    }

    public function testGETFilesOnNonExistingFile(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'NotAFile',
            'ref'          => 'master',
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testGETFilesOnNonExistingBranch(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/files?' . http_build_query([
            'path_to_file' => 'file01',
            'ref'          => 'NotABranch',
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testOPTIONSBranches(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches'));
        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSBranchesWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETBranches(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches'));

        $this->assertGETBranches($response);
    }

    public function testGETBranchesWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETBranches($response);
    }

    private function assertGETBranches(\Psr\Http\Message\ResponseInterface $response): void
    {
        $branches = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
                            'avatar_url'   => 'https://localhost/users/rest_api_tester_1/avatar.png',
                            'is_anonymous' => false,
                            'has_avatar'   => true,
                        ],
                        'commit_status' => null,
                        'verification'  => ['signature' => null],
                        "cross_references" => [
                            [
                                "ref" => $this->artifact_reference,
                                "url" => $this->artifact_url,
                                "direction" => "in",
                            ],
                        ],
                    ],
                    "html_url" => '/plugins/git/test-git/repo01?a=tree&hb=master',
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
                            'avatar_url'   => 'https://localhost/users/rest_api_tester_1/avatar.png',
                            'is_anonymous' => false,
                            'has_avatar'   => true,
                        ],
                        'commit_status' => null,
                        'verification'  => ['signature' => null],
                        "cross_references" => [],
                    ],
                    "html_url" => '/plugins/git/test-git/repo01?a=tree&hb=branch_file_02',
                ],
            ]
        );
    }

    public function testCreateABranch(): void
    {
        $post_payload = json_encode(
            [
                'branch_name' => "newbranch01",
                'reference' => "branch_file_02",
            ]
        );

        $response = $this->getResponse(
            $this->request_factory
                ->createRequest('POST', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches')
                ->withBody($this->stream_factory->createStream($post_payload)),
        );

        $this->assertEquals(201, $response->getStatusCode());

        $branche_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEqualsCanonicalizing(
            $branche_json,
            [
                "name" => "newbranch01",
                "html_url" => "/plugins/git/test-git/repo01?a=tree&hb=newbranch01",
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
                        'avatar_url'   => 'https://localhost/users/rest_api_tester_1/avatar.png',
                        'is_anonymous' => false,
                        'has_avatar'   => true,
                    ],
                    'commit_status' => null,
                    'verification'  => ['signature' => null],
                    "cross_references" => [],
                ],
            ],
        );
    }

    public function testCreateABranchMustFailWithNonValidBranchName(): void
    {
        $post_payload = json_encode(
            [
                'branch_name' => 'newbran~01',
                'reference' => "branch_file_02",
            ]
        );

        $response = $this->getResponse(
            $this->request_factory
                ->createRequest('POST', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches')
                ->withBody($this->stream_factory->createStream($post_payload)),
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCreateABranchMustFailWithNonExistingReference(): void
    {
        $post_payload = json_encode(
            [
                'branch_name' => 'newbranch02',
                'reference' => "non_existing_branch",
            ]
        );

        $response = $this->getResponse(
            $this->request_factory
                ->createRequest('POST', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches')
                ->withBody($this->stream_factory->createStream($post_payload)),
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCreateABranchMustBeForbiddenForUserThatCannotWriteInRepository(): void
    {
        $post_payload = json_encode(
            [
                'branch_name' => "newbranch02",
                'reference' => "branch_file_02",
            ]
        );

        $response = $this->getResponse(
            $this->request_factory
                ->createRequest('POST', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/branches')
                ->withBody($this->stream_factory->createStream($post_payload)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testOPTIONSTags(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tags'));
        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSTagsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tags'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETTags(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tags'));

        $this->assertGETTags($response);
    }

    public function testGETTagsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tags'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETTags($response);
    }

    public function testPATCHDefaultBranch(): void
    {
        $this->setDefaultBranch(GitDataBuilder::REPOSITORY_GIT_ID, 'branch_file_02');

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID)
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('branch_file_02', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['default_branch']);

        $this->setDefaultBranch(GitDataBuilder::REPOSITORY_GIT_ID, 'master');
    }

    private function setDefaultBranch(int $repository_id, string $branch_name): void
    {
        $patch_payload = json_encode(
            ['default_branch' => $branch_name],
            JSON_THROW_ON_ERROR
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'git/' . urlencode((string) $repository_id))->withBody($this->stream_factory->createStream($patch_payload))
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPOSTGitWithReadOnlyAdmin(): void
    {
        $project_id   = $this->getProjectId(GitDataBuilder::PROJECT_TEST_GIT_SHORTNAME);
        $post_payload = json_encode(
            [
                'project_id' => $project_id,
                'name' => 'newTestGitRepository',
            ]
        );
        $response     = $this->getResponse(
            $this->request_factory->createRequest('POST', 'git/')->withBody($this->stream_factory->createStream($post_payload)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPOSTGit(): void
    {
        $project_id   = $this->getProjectId(GitDataBuilder::PROJECT_TEST_GIT_SHORTNAME);
        $post_payload = json_encode(
            [
                'project_id' => $project_id,
                'name' => 'newTestGitRepository',
            ]
        );
        $response     = $this->getResponse(
            $this->request_factory->createRequest('POST', 'git/')->withBody($this->stream_factory->createStream($post_payload))
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testPOSTStatusWithReadOnlyAdmin(): void
    {
        $post_payload = json_encode(
            [
                "state" => "success",
                "token" => "someToken",
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/statuses/5d408503daf6f1348e264122cfa8fc89a30f7f12')->withBody($this->stream_factory->createStream($post_payload)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    private function assertGETTags(\Psr\Http\Message\ResponseInterface $response): void
    {
        $tags = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
                            'avatar_url'   => 'https://localhost/users/rest_api_tester_1/avatar.png',
                            'is_anonymous' => false,
                            'has_avatar'   => true,
                        ],
                        'commit_status' => null,
                        'verification'  => ['signature' => null],
                        "cross_references" => [],
                    ],
                ],
            ]
        );
    }

    public function testGETPullRequestsWithReadOnlyAdmin(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/pull_requests';

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertGETPullRequests($response);
    }

    public function testOPTIONSGetPullRequests(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/pull_requests'));
        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETPullRequests(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/pull_requests';

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertGETPullRequests($response);
    }

    private function assertGETPullRequests(\Psr\Http\Message\ResponseInterface $response): void
    {
        $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(2, count($content['collection']));
        $this->assertEquals(2, $content['total_size']);
    }

    public function testOPTIONSGetPullRequestsAuthors(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/pull_requests_authors'));
        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETPullRequestsAuthors(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/pull_requests_authors';

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));
        $content  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, count($content));
    }

    public function testOPTIONSGetCommits(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/commits/whateverreference'));
        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETCommits(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/commits/8957aa17cf3f56658d91d1c67f60e738f3fdcb3e'));

        $this->assertGETCommits($response);
    }

    public function testGETCommitsWithAnInvalidReference(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/commits/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));

        self::assertEquals(404, $response->getStatusCode());
    }

    private function assertGetCommits(\Psr\Http\Message\ResponseInterface $response): void
    {
        $commit = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEqualsCanonicalizing(
            $commit,
            [
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
                    'avatar_url'   => 'https://localhost/users/rest_api_tester_1/avatar.png',
                    'is_anonymous' => false,
                    'has_avatar'   => true,
                ],
                'commit_status' => null,
                'verification'  => ['signature' => null],
                "cross_references" => [
                    [
                        "ref" => $this->artifact_reference,
                        "url" => $this->artifact_url,
                        "direction" => "in",
                    ],
                ],
            ]
        );
    }

    public function testOPTIONSGetTree(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tree?' . http_build_query(
            [
                'path' => '',
                'ref' => 'master',
            ]
        )));
        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETTreeWithEmptyPath(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tree?' . http_build_query(
            [
                'path' => '',
                'ref'  => 'master',
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertGetTreeWithEmptyPath($response);
    }

    public function testGETTreeWithAFilePathReturns404(): void
    {
        $url = 'git/' . GitDataBuilder::REPOSITORY_GIT_ID . '/tree?' . http_build_query(
            [
                'path' => 'file01',
                'ref'  => 'master',
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        self::assertEquals(404, $response->getStatusCode());
    }

    private function assertGetTreeWithEmptyPath(\Psr\Http\Message\ResponseInterface $response): void
    {
        $commit = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEqualsCanonicalizing(
            $commit,
            [
                [
                    'id'   => '459385229609d3c5f847e75ae61b3859cf90f159',
                    'name' => 'README.mkd',
                    'path' => 'README.mkd',
                    'type' => 'blob',
                    'mode' => '100644',
                ],
                [
                    'id'   => '8e72e5b6f640d6df27c219b039c6430d4ed96a1a',
                    'name' => 'file01',
                    'path' => 'file01',
                    'type' => 'blob',
                    'mode' => '100644',
                ],
            ]
        );
    }
}
