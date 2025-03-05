<?php
/**
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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

namespace Tuleap\SVN\REST;

use REST_TestDataBuilder;

require_once dirname(__FILE__) . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class RepositoryTest extends TestBase
{
    private function getResponseWithProjectMember($request)
    {
        return $this->getResponse(
            $request,
            REST_TestDataBuilder::TEST_USER_3_NAME
        );
    }

    public function testGETRepositoryForProjectAdmin()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'svn/1'));

        $this->assertRepositoryForAdmin($response);
    }

    public function testGETRepositoryForRESTReadOnlyUser()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'svn/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertRepositoryForAdmin($response);
    }

    private function assertRepositoryForAdmin(\Psr\Http\Message\ResponseInterface $response)
    {
        $repository = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals($repository['svn_url'], $this->svn_domain . '/svnplugin/SVN-plugin-test/repo01');
        $this->assertArrayHasKey('settings', $repository);
        $this->assertEquals(
            $repository['settings']['commit_rules'],
            [
                'is_reference_mandatory'           => false,
                'is_commit_message_change_allowed' => false,
            ]
        );

        $this->assertEquals(
            $repository['settings']['immutable_tags'],
            [
                'paths'     => [],
                'whitelist' => [],
            ]
        );

        $this->assertEquals(
            $repository['settings']['access_file'],
            '[/] * = rw @members = rw'
        );

        $this->assertEqualsCanonicalizing(
            $repository['settings']['email_notifications'],
            [
                [
                    'user_groups' => [],
                    'users'       => [],
                    'emails'      => ['project-announce@list.example.com', 'project-devel@lists.example.com'],
                    'path'        => '/tags',
                ],
                [
                    'user_groups' => [],
                    'users'       => [],
                    'emails'      => ['project-svn@list.example.com'],
                    'path'        => '/trunk',
                ],
            ]
        );
    }

    public function testGETRepositoryForProjectMember()
    {
        $response = $this->getResponseWithProjectMember($this->request_factory->createRequest('GET', 'svn/1'));

        $repository = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals($repository['svn_url'], $this->svn_domain . '/svnplugin/SVN-plugin-test/repo01');
        $this->assertArrayNotHasKey('settings', $repository);
    }

    /**
     * @depends testGETRepositoryForProjectAdmin
     * @depends testGETRepositoryForProjectMember
     */
    public function testDELETERepositoryForProjectAdmin()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'svn/1')
        );

        $this->assertEquals($response->getStatusCode(), 202);
    }

    /**
     * @depends testGETRepositoryForProjectAdmin
     * @depends testGETRepositoryForProjectMember
     */
    public function testDELETERepositoryForProjectMember()
    {
        $response = $this->getResponseWithProjectMember(
            $this->request_factory->createRequest('DELETE', 'svn/1')
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGETRepositoryForProjectAdmin
     * @depends testGETRepositoryForProjectMember
     */
    public function testDELETERepositoryForRESTReadOnlyUserNotInvolvedInProject()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'svn/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPOSTRepositoryForRESTReadOnlyUserNotInvolvedInProject()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository',
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPOSTRepositoryForProjectAdmin()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository',
            ]
        );

        $response   = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $repository = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'my_repository');
        $this->assertEquals(
            $repository['settings']['commit_rules'],
            [
                'is_reference_mandatory'           => false,
                'is_commit_message_change_allowed' => false,
            ]
        );
        $this->assertEquals(
            $repository['settings']['immutable_tags'],
            [
                'paths'     => [],
                'whitelist' => [],
            ]
        );
        $this->assertEquals(
            $repository['settings']['access_file'],
            ''
        );
    }

    public function testPOSTRepositoryForProjectAdminWithCustomSettings()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_02',
                'settings'   => [
                    'commit_rules'        => [
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => true,
                    ],
                    'immutable_tags'      => [
                        'paths'     => [
                            '/tags1',
                            '/tags2',
                        ],
                        'whitelist' => [
                            '/white1',
                            '/white2',
                        ],
                    ],
                    'access_file'         => "[/] * = rw\r\n@members = rw",
                    'email_notifications' => [
                        [
                            'user_groups' => [],
                            'users'       => [102, 103],
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'path'        => '/tags',
                        ],
                        [
                            'user_groups' => [
                                $this->user_group_1_id,
                                $this->user_group_2_id,
                            ],
                            'users'       => [],
                            'emails'      => ['project-svn@list.example.com'],
                            'path'        => '/trunk',
                        ],
                    ],
                ],
            ]
        );

        $response   = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $repository = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'my_repository_02');
        $this->assertEquals(
            $repository['settings']['commit_rules'],
            [
                'is_reference_mandatory'           => true,
                'is_commit_message_change_allowed' => true,
            ]
        );
        $this->assertEquals(
            $repository['settings']['immutable_tags'],
            [
                'paths'     => ['/tags1', '/tags2'],
                'whitelist' => ['/white1', '/white2'],
            ]
        );
        $this->assertEquals(
            $repository['settings']['access_file'],
            "[/] * = rw\r\n@members = rw"
        );

        $this->assertEquals(
            $repository['settings']['email_notifications'],
            [
                [
                    'user_groups' => [],
                    'users'       => [$this->user_102, $this->user_103],
                    'emails'      => ['project-announce@list.example.com', 'project-devel@lists.example.com'],
                    'path'        => '/tags',
                ],
                [
                    'user_groups' => [$this->user_group_101, $this->user_group_102],
                    'users'       => [],
                    'emails'      => ['project-svn@list.example.com'],
                    'path'        => '/trunk',
                ],
            ]
        );
    }

    public function testPOSTRepositoryForProjectMember()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_03',
            ]
        );

        $response = $this->getResponseWithProjectMember($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPOSTWithALayout()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_04',
                'settings'   => [
                    'layout' => ['/trunk', '/branches'],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 201);
    }

    public function testPUTRepositoryRESTReadOnlyUserNotInvolvedInProject()
    {
        $data = json_encode(
            [
                'settings' => [
                    'commit_rules'        => [
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false,
                    ],
                    'access_file'         => "[/]\r\n* = rw\r\n@members = rw",
                    'immutable_tags'      => [
                        'paths'     => [],
                        'whitelist' => [],
                    ],
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'users'       => [102],
                            'user_groups' => [
                                $this->user_group_1_id,
                                $this->user_group_2_id,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'svn/1')->withBody($this->stream_factory->createStream($data)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPUTRepository(): void
    {
        $data = json_encode(
            [
                'settings' => [
                    'commit_rules'        => [
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false,
                    ],
                    'access_file'         => "[/]\r\n* = rw\r\n@members = rw",
                    'immutable_tags'      => [
                        'paths'     => [],
                        'whitelist' => [],
                    ],
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'users'       => [102],
                            'user_groups' => [
                                $this->user_group_1_id,
                                $this->user_group_2_id,
                            ],
                        ],
                        [
                            'path'        => '/only_ugroup_notifications',
                            'emails'      => [],
                            'users'       => [],
                            'user_groups' => [
                                $this->user_group_1_id,
                                $this->user_group_2_id,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'svn/1')->withBody($this->stream_factory->createStream($data)));

        $repository = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals(
            $repository['settings']['commit_rules'],
            [
                'is_reference_mandatory'           => true,
                'is_commit_message_change_allowed' => false,
            ]
        );
        $this->assertEquals(
            $repository['settings']['immutable_tags'],
            [
                'paths'     => [],
                'whitelist' => [],
            ]
        );
        $this->assertEquals(
            $repository['settings']['access_file'],
            "[/]\r\n* = rw\r\n@members = rw"
        );

        $this->assertEqualsCanonicalizing(
            $repository['settings']['email_notifications'],
            [
                [
                    'path'        => '/tags',
                    'emails'      => [
                        'project-announce@list.example.com',
                        'project-devel@lists.example.com',
                    ],
                    'user_groups' => [$this->user_group_101, $this->user_group_102],
                    'users'       => [$this->user_102],
                ],
                [
                    'path'        => '/only_ugroup_notifications',
                    'emails'      => [],
                    'user_groups' => [$this->user_group_101, $this->user_group_102],
                    'users'       => [],
                ],
            ]
        );
    }

    public function testPUTRepositoryWithMassiveAllowlistIsRejected(): void
    {
        $data = json_encode(
            [
                'settings' => [
                    'commit_rules' => [
                        'is_reference_mandatory' => true,
                        'is_commit_message_change_allowed' => false,
                    ],
                    'access_file' => "[/]\r\n* = rw\r\n@members = rw",
                    'immutable_tags' => [
                        'paths' => [],
                        'whitelist' => array_fill(0, 65535, '/a'),
                    ],
                    'email_notifications' => [],
                ],
            ],
            JSON_THROW_ON_ERROR
        );

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'svn/1')->withBody($this->stream_factory->createStream($data)));

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testOPTIONSId()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'svn/1'));

        $this->assertEquals(
            ['OPTIONS', 'GET', 'PUT', 'DELETE'],
            explode(', ', $response->getHeaderLine('Allow'))
        );
    }

    public function testOPTIONS()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'svn'),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testAllOptionsWithRESTReadOnlyUserNotInvolvedInProject()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'svn/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(
            ['OPTIONS', 'GET', 'PUT', 'DELETE'],
            explode(', ', $response->getHeaderLine('Allow'))
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'svn'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }
}
