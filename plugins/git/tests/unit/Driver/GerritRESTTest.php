<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Driver;

use ColinODell\PsrTestLogger\TestLogger;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_Exception;
use Git_Driver_Gerrit_User;
use Git_Driver_GerritREST;
use Git_RemoteServer_GerritServer;
use GitRepository;
use Http\Message\RequestMatcher\RequestMatcher;
use Http\Mock\Client;
use Project;
use ProjectDeletionException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GerritRESTTest extends TestCase
{
    private TestLogger $logger;
    private Client $http_client;
    private ResponseFactoryInterface $response_factory;
    private StreamFactoryInterface $stream_factory;
    private Git_RemoteServer_GerritServer $gerrit_server;
    private Git_Driver_GerritREST $driver;

    protected function setUp(): void
    {
        $this->logger      = new TestLogger();
        $this->http_client = new Client();

        $this->response_factory = HTTPFactoryBuilder::responseFactory();
        $this->stream_factory   = HTTPFactoryBuilder::streamFactory();

        $this->gerrit_server = new Git_RemoteServer_GerritServer(
            1,
            'gerrit.example.com',
            29418,
            443,
            'login',
            '/path/to/identify/file',
            'replication_key',
            true,
            Git_RemoteServer_GerritServer::GERRIT_VERSION_2_8_PLUS,
            'http_password',
            'replication_password'
        );

        $this->driver = new Git_Driver_GerritREST(
            new GerritHTTPClientFactory($this->http_client),
            HTTPFactoryBuilder::requestFactory(),
            $this->stream_factory,
            $this->logger
        );
    }

    public function testAddsAnIncludedGroup(): void
    {
        $this->http_client->addResponse($this->response_factory->createResponse(201));

        $group_name          = 'grp';
        $included_group_name = 'proj grp';

        $this->driver->addIncludedGroup($this->gerrit_server, $group_name, $included_group_name);

        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('PUT', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/groups/' . urlencode($group_name) . '/groups/' . urlencode($included_group_name), $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testItDetectsDeleteProjectPluginForGerritLesserThan214(): void
    {
        $response = $this->response_factory->createResponse()->withBody(
            $this->stream_factory->createStream(
                ")]}'\n" . json_encode(['deleteproject' => 1])
            )
        );
        $this->http_client->addResponse($response);

        self::assertTrue($this->driver->isDeletePluginEnabled($this->gerrit_server));
        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('GET', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/plugins/', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItDetectsDeleteProjectPluginForGerritGreaterThan214(): void
    {
        $response = $this->response_factory->createResponse()->withBody(
            $this->stream_factory->createStream(
                ")]}'\n" . json_encode(['delete-project' => 1])
            )
        );
        $this->http_client->addResponse($response);

        self::assertTrue($this->driver->isDeletePluginEnabled($this->gerrit_server));
        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('GET', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/plugins/', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItDetectsAbsenceOfDeleteProjectPlugin(): void
    {
        $response = $this->response_factory->createResponse()->withBody(
            $this->stream_factory->createStream(
                ")]}'\n" . json_encode(['replication' => 1])
            )
        );
        $this->http_client->addResponse($response);

        self::assertFalse($this->driver->isDeletePluginEnabled($this->gerrit_server));
        self::assertTrue($this->logger->hasInfoRecords());
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItThrowsAProjectDeletionExceptionIfThereAreOpenChanges(): void
    {
        $this->http_client->addResponse($this->response_factory->createResponse(400));

        $this->expectException(ProjectDeletionException::class);
        $this->driver->deleteProject($this->gerrit_server, 'project');
        self::assertTrue($this->logger->hasInfoRecords());
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testReturnsTrueIfGroupExists(): void
    {
        $group_name = 'Group Name';

        $this->http_client->addResponse(
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream(")]}'\n" . json_encode(['id' => 1, 'name' => $group_name]))
            )
        );

        self::assertTrue($this->driver->doesTheGroupExist($this->gerrit_server, $group_name));
        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('GET', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/groups/' . urlencode($group_name), $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testReturnsFalseIfGroupDoNotExists(): void
    {
        $this->http_client->addResponse($this->response_factory->createResponse(404));

        self::assertFalse($this->driver->doesTheGroupExist($this->gerrit_server, 'GroupName'));
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testCreatesGroupsIfItNotExistsOnGerrit(): void
    {
        $group_name = 'firefox/project_members';

        $owner = 'firefox/project_admins';

        $this->http_client->on(
            new RequestMatcher($owner, null, ['GET']),
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream(
                    ")]}'\n" . json_encode(['id' => 'owner_uuid'])
                )
            )
        );
        $this->http_client->on(
            new RequestMatcher(null, null, ['PUT']),
            $this->response_factory->createResponse(201)
        );


        $this->driver->createGroup($this->gerrit_server, $group_name, $owner);
        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('PUT', $request->getMethod());
        self::assertJsonStringEqualsJsonString('{"owner_id":"owner_uuid"}', $request->getBody()->getContents());
        $request_uri = $request->getUri();
        self::assertEquals('/a/groups/' . urlencode($group_name), $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoThatContains('Gerrit REST driver: Create group ' . $group_name));
        self::assertTrue($this->logger->hasInfoThatContains('Gerrit REST driver: Group ' . $group_name . ' successfully created'));
    }

    public function testCreateGroupDealsWithGroupAlreadyExistingOnGerrit(): void
    {
        $group_name = 'firefox/project_members';

        $owner = 'firefox/project_admins';

        $this->http_client->on(
            new RequestMatcher($owner, null, ['GET']),
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream(
                    ")]}'\n" . json_encode(['id' => 'owner_uuid'])
                )
            )
        );
        $this->http_client->on(
            new RequestMatcher(null, null, ['PUT']),
            $this->response_factory->createResponse(409)
        );


        $this->driver->createGroup($this->gerrit_server, $group_name, $owner);
        self::assertTrue($this->logger->hasInfoThatContains('Gerrit REST driver: Create group ' . $group_name));
        self::assertTrue($this->logger->hasInfoThatContains('Gerrit REST driver: Group ' . $group_name . ' already exists'));
    }

    public function testItCreatesGroupWithoutOwnerWhenSelfOwnedToAvoidChickenEggIssue(): void
    {
        $this->http_client->on(
            new RequestMatcher(Git_Driver_GerritREST::DEFAULT_GROUP_OWNER, null, ['GET']),
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream(
                    ")]}'\n" . json_encode(['id' => 'owner_uuid'])
                )
            )
        );
        $this->http_client->on(
            new RequestMatcher(null, null, ['PUT']),
            $this->response_factory->createResponse(409)
        );

        $this->driver->createGroup($this->gerrit_server, 'firefox/project_admins', 'firefox/project_admins');
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testAsksGerritForTheGroupUUID(): void
    {
        $get_group_response = <<<EOS
        )]}'
        {
          "kind": "gerritcodereview#group",
          "url": "#/admin/groups/uuid-a1e6742f55dc890205b9db147826964d12c9a775",
          "options": {},
          "group_id": 8,
          "owner": "enalean",
          "owner_id": "a1e6742f55dc890205b9db147826964d12c9a775",
          "id": "a1e6742f55dc890205b9db147826964d12c9a775",
          "name": "enalean"
        }
        EOS;

        $this->http_client->addResponse(
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream($get_group_response)
            )
        );

        self::assertEquals(
            'a1e6742f55dc890205b9db147826964d12c9a775',
            $this->driver->getGroupUUID($this->gerrit_server, 'enalean')
        );
    }

    public function testAsksGerritForTheGroupId(): void
    {
        $get_group_response = <<<EOS
        )]}'
        {
          "kind": "gerritcodereview#group",
          "url": "#/admin/groups/uuid-a1e6742f55dc890205b9db147826964d12c9a775",
          "options": {},
          "group_id": 8,
          "owner": "enalean",
          "owner_id": "a1e6742f55dc890205b9db147826964d12c9a775",
          "id": "a1e6742f55dc890205b9db147826964d12c9a775",
          "name": "enalean"
        }
        EOS;

        $this->http_client->addResponse(
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream($get_group_response)
            )
        );

        self::assertEquals(
            8,
            $this->driver->getGroupId($this->gerrit_server, 'enalean')
        );
    }

    public function testReturnsNullIdIfNotFound(): void
    {
        $this->http_client->addResponse(
            $this->response_factory->createResponse(404)
        );

        self::assertNull($this->driver->getGroupId($this->gerrit_server, 'enalean'));
    }

    public function testReturnsNullUUIDIfNotFound(): void
    {
        $this->http_client->addResponse(
            $this->response_factory->createResponse(404)
        );

        self::assertNull($this->driver->getGroupUUID($this->gerrit_server, 'enalean'));
    }

    public function testReturnsAllGroups(): void
    {
        $raiponce = <<<EOS
        )]}'
        {
          "enalean": {
            "kind": "gerritcodereview#group",
            "url": "#/admin/groups/uuid-6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
            "options": {},
            "group_id": 7,
            "owner": "enalean",
            "owner_id": "6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
            "id": "6ef56904c11e6d53c8f2f3657353faaac74bfc6d"
          },
          "grp": {
            "kind": "gerritcodereview#group",
            "url": "#/admin/groups/uuid-b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
            "options": {},
            "group_id": 6,
            "owner": "grp",
            "owner_id": "b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
            "id": "b99e4455ca98f2ec23d9250f69617e34ceae6bd6"
          }
        }
        EOS;

        $this->http_client->addResponse(
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream($raiponce)
            )
        );

        $expected_result = [
            'enalean' => '6ef56904c11e6d53c8f2f3657353faaac74bfc6d',
            'grp'     => 'b99e4455ca98f2ec23d9250f69617e34ceae6bd6',
        ];

        self::assertEquals($expected_result, $this->driver->getAllGroups($this->gerrit_server));
        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('GET', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/groups/', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testExecutesTheCreateCommandForProjectOnTheGerritServer(): void
    {
        $this->http_client->addResponse(
            $this->response_factory->createResponse(201)
        );

        $project_name = 'project_name';
        $repo_name    = 'repo_name';

        $created_project_name = $this->driver->createProject($this->gerrit_server, $this->buildGitRepository($project_name, $repo_name), $project_name);

        $gerrit_project_name = "$project_name/$repo_name";
        self::assertEquals($gerrit_project_name, $created_project_name);

        $expected_json_data = json_encode(
            [
                'description' => "Migration of $gerrit_project_name from Tuleap",
                'parent'      => $project_name,
            ]
        );
        $request            = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('PUT', $request->getMethod());
        self::assertJsonStringEqualsJsonString($expected_json_data, $request->getBody()->getContents());
        self::assertEquals(Git_Driver_GerritREST::MIME_JSON, $request->getHeaderLine(Git_Driver_GerritREST::HEADER_CONTENT_TYPE));
        $request_uri = $request->getUri();
        self::assertEquals('/a/projects/' . urlencode($gerrit_project_name), $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testExecutesTheCreateCommandForParentProjectOnTheGerritServer(): void
    {
        $this->http_client->addResponse(
            $this->response_factory->createResponse(201)
        );

        $project_name = 'project_name';
        $project      = $this->buildProject($project_name);

        $this->driver->createProjectWithPermissionsOnly($this->gerrit_server, $project, 'firefox/project_admins');

        $expected_json_data = json_encode(
            [
                'description'      => "Migration of $project_name from Tuleap",
                'permissions_only' => true,
                'owners'           => [
                    'firefox/project_admins',
                ],
            ]
        );
        $request            = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('PUT', $request->getMethod());
        self::assertJsonStringEqualsJsonString($expected_json_data, $request->getBody()->getContents());
        self::assertEquals(Git_Driver_GerritREST::MIME_JSON, $request->getHeaderLine(Git_Driver_GerritREST::HEADER_CONTENT_TYPE));
        $request_uri = $request->getUri();
        self::assertEquals('/a/projects/' . urlencode($project_name), $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testRaisesAGerritDriverExceptionWhenAnIssueHappensOnProjectCreation(): void
    {
        $this->http_client->addResponse(
            $this->response_factory->createResponse(500)
        );

        $this->expectException(Git_Driver_Gerrit_Exception::class);
        $this->driver->createProject(
            $this->gerrit_server,
            $this->buildGitRepository('project', 'repo'),
            'parent_project'
        );
        self::assertTrue($this->logger->hasInfoRecords());
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testPutsProjectInReadOnly(): void
    {
        $project_name = 'project_name';

        $this->driver->makeGerritProjectReadOnly($this->gerrit_server, $project_name);

        $expected_json_data = json_encode([
            'state' => 'READ_ONLY',
        ]);
        $request            = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('PUT', $request->getMethod());
        self::assertJsonStringEqualsJsonString($expected_json_data, $request->getBody()->getContents());
        self::assertEquals(Git_Driver_GerritREST::MIME_JSON, $request->getHeaderLine(Git_Driver_GerritREST::HEADER_CONTENT_TYPE));
        $request_uri = $request->getUri();
        self::assertEquals('/a/projects/' . urlencode($project_name) . '/config', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testAddProjectInheritance(): void
    {
        $project_name = 'project_name';

        $this->driver->setProjectInheritance($this->gerrit_server, $project_name, 'prj');

        $expected_json_data = json_encode([
            'parent' => 'prj',
        ]);
        $request            = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('PUT', $request->getMethod());
        self::assertJsonStringEqualsJsonString($expected_json_data, $request->getBody()->getContents());
        self::assertEquals(Git_Driver_GerritREST::MIME_JSON, $request->getHeaderLine(Git_Driver_GerritREST::HEADER_CONTENT_TYPE));
        $request_uri = $request->getUri();
        self::assertEquals('/a/projects/' . urlencode($project_name) . '/parent', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testResetsProjectInheritance(): void
    {
        $project_name = 'project_name';

        $this->driver->resetProjectInheritance($this->gerrit_server, $project_name);

        $expected_json_data = json_encode([
            'parent' => Git_Driver_Gerrit::DEFAULT_PARENT_PROJECT,
        ]);
        $request            = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('PUT', $request->getMethod());
        self::assertJsonStringEqualsJsonString($expected_json_data, $request->getBody()->getContents());
        self::assertEquals(Git_Driver_GerritREST::MIME_JSON, $request->getHeaderLine(Git_Driver_GerritREST::HEADER_CONTENT_TYPE));
        $request_uri = $request->getUri();
        self::assertEquals('/a/projects/' . urlencode($project_name) . '/parent', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testDeletesProject(): void
    {
        $this->http_client->addResponse($this->response_factory->createResponse(204));

        $project_name = 'gerrit/project_name';
        $this->driver->deleteProject($this->gerrit_server, $project_name);

        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('POST', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/projects/' . urlencode($project_name) . '/delete-project~delete', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testTriesToDeleteAnAlreadyDeletedProjectWithoutThrowingAnException(): void
    {
        $this->http_client->addResponse($this->response_factory->createResponse(404));

        $project_name = 'gerrit/already_deleted_project_name';
        $this->driver->deleteProject($this->gerrit_server, $project_name);

        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('POST', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/projects/' . urlencode($project_name) . '/delete-project~delete', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    private function buildGitRepository(string $project_name, string $repo_name): GitRepository
    {
        return GitRepositoryTestBuilder::aProjectRepository()->inProject($this->buildProject($project_name))->withName($repo_name)->build();
    }

    private function buildProject(string $project_name): Project
    {
        return ProjectTestBuilder::aProject()->withUnixName($project_name)->build();
    }

    public function testAddsUserToGroup(): void
    {
        $group_name = 'group_name';
        $user       = $this->buildGerritUser();

        $this->http_client->addResponse($this->response_factory->createResponse(201));

        $this->driver->addUserToGroup($this->gerrit_server, $user, $group_name);

        $requests = $this->http_client->getRequests();
        self::assertCount(1, $requests);
        $request = $requests[0];
        self::assertEquals('PUT', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/groups/' . urlencode($group_name) . '/members/' . urlencode($user->getSSHUserName()), $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testRemovesUserFromGroup(): void
    {
        $group_name = 'group_name';
        $user       = $this->buildGerritUser();

        $this->http_client->addResponse($this->response_factory->createResponse(204));

        $this->driver->removeUserFromGroup($this->gerrit_server, $user, $group_name);

        $requests = $this->http_client->getRequests();
        self::assertCount(1, $requests);
        $request = $requests[0];
        self::assertEquals('DELETE', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/groups/' . urlencode($group_name) . '/members/' . urlencode($user->getSSHUserName()), $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testRemovesAllMembers(): void
    {
        $group_name = 'group_name';

        $this->http_client->on(
            new RequestMatcher(urlencode($group_name) . '/members', null, ['GET']),
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream(
                    <<<EOS
                    )]}'
                    [
                      {
                        "_account_id": 1000000,
                        "name": "gerrit-adm",
                        "username": "gerrit-adm",
                          "avatars": []
                      },
                      {
                        "_account_id": 1000002,
                        "name": "testUser",
                        "email": "test@test.test",
                        "username": "testUser",
                        "avatars": []
                      }
                    ]
                    EOS
                )
            )
        );
        $this->http_client->on(
            new RequestMatcher('members.delete', null, ['POST']),
            $this->response_factory->createResponse(204)
        );

        $this->driver->removeAllGroupMembers($this->gerrit_server, $group_name);

        $expected_json_data = json_encode([
            'members' => ['gerrit-adm', 'testUser'],
        ]);
        self::assertCount(2, $this->http_client->getRequests());
        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('POST', $request->getMethod());
        self::assertJsonStringEqualsJsonString($expected_json_data, $request->getBody()->getContents());
        self::assertEquals(Git_Driver_GerritREST::MIME_JSON, $request->getHeaderLine(Git_Driver_GerritREST::HEADER_CONTENT_TYPE));
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testAddsSSHKeyForUser(): void
    {
        $ssh_key = 'AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw==';

        $user = $this->buildGerritUser();

        $this->driver->addSSHKeyToAccount($this->gerrit_server, $user, $ssh_key);

        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('POST', $request->getMethod());
        self::assertEquals($ssh_key, $request->getBody()->getContents());
        self::assertEquals(Git_Driver_GerritREST::MIME_TEXT, $request->getHeaderLine(Git_Driver_GerritREST::HEADER_CONTENT_TYPE));
        $request_uri = $request->getUri();
        self::assertEquals('/a/accounts/' . urlencode($user->getSSHUserName()) . '/sshkeys', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testRemovesSSHKeyForUser(): void
    {
        $this->http_client->on(
            new RequestMatcher(null, null, ['GET']),
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream(
                    <<<EOS
                    )]}'
                    [
                      {
                        "seq": 1,
                        "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...azertyAw\u003d\u003d john.doe@example.com",
                        "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...azertyAw\u003d\u003d",
                        "algorithm": "ssh-rsa",
                        "comment": "john.doe@example.com",
                        "valid": true
                      },
                      {
                        "seq": 2,
                        "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d john.doe@example.com",
                        "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d",
                        "algorithm": "ssh-rsa",
                        "comment": "john.doe@example.com",
                        "valid": true
                      }
                    ]
                    EOS
                )
            )
        );
        $this->http_client->on(
            new RequestMatcher(null, null, ['DELETE']),
            $this->response_factory->createResponse(204)
        );

        $ssh_key = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw== john.doe@example.com';
        $user    = $this->buildGerritUser();

        $this->driver->removeSSHKeyFromAccount($this->gerrit_server, $user, $ssh_key);

        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('DELETE', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/accounts/' . urlencode($user->getSSHUserName()) . '/sshkeys/2', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testRemovesMultipleTimeTheSSHKeyForUserIfFoundMultipleTimes(): void
    {
        $this->http_client->on(
            new RequestMatcher(null, null, ['GET']),
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream(
                    <<<EOS
                    )]}'
                    [
                      {
                        "seq": 1,
                        "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...azertyAw\u003d\u003d john.doe@example.com",
                        "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...azertyAw\u003d\u003d",
                        "algorithm": "ssh-rsa",
                        "comment": "john.doe@example.com",
                        "valid": true
                      },
                      {
                        "seq": 2,
                        "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d john.doe@example.com",
                        "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d",
                        "algorithm": "ssh-rsa",
                        "comment": "john.doe@example.com",
                        "valid": true
                      },
                      {
                        "seq": 3,
                        "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d another comment that do not match the requested comment",
                        "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d",
                        "algorithm": "ssh-rsa",
                        "comment": "another comment that do not match the requested comment",
                        "valid": true
                      }
                    ]
                    EOS
                )
            )
        );
        $this->http_client->on(
            new RequestMatcher('sshkeys/2', null, ['DELETE']),
            $this->response_factory->createResponse(204)
        );
        $this->http_client->on(
            new RequestMatcher('sshkeys/3', null, ['DELETE']),
            $this->response_factory->createResponse(204)
        );

        $ssh_key = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw== john.doe@example.com';

        $this->driver->removeSSHKeyFromAccount($this->gerrit_server, $this->buildGerritUser(), $ssh_key);

        self::assertCount(3, $this->http_client->getRequests());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    private function buildGerritUser(): Git_Driver_Gerrit_User
    {
        $user = $this->createMock(Git_Driver_Gerrit_User::class);
        $user->method('getEmail')->willReturn('email@example.com');
        $user->method('getSSHUserName')->willReturn('sshusername');
        $user->method('getRealName')->willReturn('Real Name');
        $user->method('getWebUserName')->willReturn('Web Username');
        return $user;
    }

    public function testReturnsFalseIfParentProjectDoesNotExists(): void
    {
        $this->http_client->addResponse($this->response_factory->createResponse(404));

        self::assertFalse($this->driver->doesTheParentProjectExist($this->gerrit_server, 'project_name'));
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testReturnsTrueIfParentProjectExists(): void
    {
        $this->http_client->addResponse($this->response_factory->createResponse(200));

        $project_name = 'project/name';

        self::assertTrue($this->driver->doesTheParentProjectExist($this->gerrit_server, $project_name));

        $request = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('GET', $request->getMethod());
        $request_uri = $request->getUri();
        self::assertEquals('/a/projects/' . urlencode($project_name), $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testReturnsFalseIfProjectDoesNotExists(): void
    {
        $this->http_client->addResponse($this->response_factory->createResponse(404));

        self::assertFalse($this->driver->doesTheProjectExist($this->gerrit_server, 'project_name'));
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testReturnsTrueIfProjectExists(): void
    {
        $this->http_client->addResponse($this->response_factory->createResponse(200));

        self::assertTrue($this->driver->doesTheProjectExist($this->gerrit_server, 'project_name'));
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testRemovesAllIncludedGroups(): void
    {
        $group_name = 'parent group';

        $this->http_client->on(
            new RequestMatcher(null, null, ['GET']),
            $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream(
                    <<<EOS
                    )]}'
                    [
                      {
                        "kind": "gerritcodereview#group",
                        "url": "#/admin/groups/uuid-6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
                        "options": {},
                        "group_id": 7,
                        "owner": "enalean",
                        "owner_id": "6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
                        "id": "6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
                        "name": "enalean"
                      },
                      {
                        "kind": "gerritcodereview#group",
                        "url": "#/admin/groups/uuid-b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
                        "options": {},
                        "group_id": 6,
                        "owner": "another group",
                        "owner_id": "b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
                        "id": "b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
                        "name": "another group"
                      }
                    ]
                    EOS
                )
            )
        );
        $this->http_client->on(
            new RequestMatcher('groups.delete', null, ['POST']),
            $this->response_factory->createResponse(204)
        );

        $this->driver->removeAllIncludedGroups($this->gerrit_server, $group_name);

        $expected_json_data = json_encode(['groups' => ['enalean', 'another group']]);
        $request            = $this->http_client->getLastRequest();
        assert($request instanceof RequestInterface);
        self::assertEquals('POST', $request->getMethod());
        self::assertJsonStringEqualsJsonString($expected_json_data, $request->getBody()->getContents());
        self::assertEquals(Git_Driver_GerritREST::MIME_JSON, $request->getHeaderLine(Git_Driver_GerritREST::HEADER_CONTENT_TYPE));
        $request_uri = $request->getUri();
        self::assertEquals('/a/groups/' . urlencode($group_name) . '/groups.delete', $request_uri->getPath());
        self::assertEquals($this->gerrit_server->getHost(), $request_uri->getHost());
        self::assertTrue($this->logger->hasInfoRecords());
    }
}
