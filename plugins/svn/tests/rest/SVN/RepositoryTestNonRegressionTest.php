<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\REST;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class RepositoryTestNonRegressionTest extends TestBase
{
    public const TULEAP_MAGIC_GROUP_ID_ANONYMOUS = 1;
    public const TULEAP_MAGIC_GROUP_ID_MEMBERS   = 3;

    public function testPOSTRepositoryWithMissingKey()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_05',
                'settings'   => [
                    'commit_rules' => [
                        'is_reference_mandatory' => true,
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithMissingPathKey()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_06',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithMissingEmailsKey()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_07',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'users'       => [],
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithMissingUserGroupsKey()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_07',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'path'   => '/tags',
                            'users'  => [],
                            'emails' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithMissingUsersKey()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_08',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'path'        => '/tags',
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithEmptyNotification()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_08',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'users'       => [],
                            'emails'      => [],
                            'path'        => '/tags',
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithDuplicatePath()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_07',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => [],
                        ],
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'test@list.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithNonExistingGroupId()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_07',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => ['1000'],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPOSTWithUGroupIdFromAnOtherProject()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_07',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => ['110_' . self::TULEAP_MAGIC_GROUP_ID_MEMBERS],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithUnsupportedDynamicUGroupId()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_07',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => [$this->svn_project_id . '_' . self::TULEAP_MAGIC_GROUP_ID_ANONYMOUS],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithSupportedDynamicUGroupId()
    {
        $params = json_encode(
            [
                'project_id' => $this->svn_project_id,
                'name'       => 'my_repository_07',
                'settings'   => [
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                                'project-devel@lists.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => [$this->svn_project_id . '_' . self::TULEAP_MAGIC_GROUP_ID_MEMBERS],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 201);
    }

    public function testPUTRepositoryWithEmptyAccessFile()
    {
        $data = json_encode(
            [
                'settings' => [
                    'commit_rules'        => [
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false,
                    ],
                    'access_file'         => '',
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
                            'users'       => [],
                            'user_groups' => [],
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
            ''
        );
    }

    public function testPUTRepositoryWithMissingCommitRulesKey()
    {
        $params = json_encode(
            [
                'settings' => [
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
                            ],
                            'users'       => [],
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingImmutableTagKey()
    {
        $params = json_encode(
            [
                'settings' => [
                    'commit_rules'        => [
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false,
                    ],
                    'access_file'         => "[/]\r\n* = rw\r\n@members = rw",
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingAccessFileKey()
    {
        $params = json_encode(
            [
                'settings' => [
                    'commit_rules'        => [
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false,
                    ],
                    'immutable_tags'      => [
                        'paths'     => [],
                        'whitelist' => [],
                    ],
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingEmailNotificationsKey()
    {
        $params = json_encode(
            [
                'settings' => [
                    'commit_rules'   => [
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false,
                    ],
                    'immutable_tags' => [
                        'paths'     => [],
                        'whitelist' => [],
                    ],
                    'access_file'    => "[/]\r\n* = rw\r\n@members = rw",
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingCommitRulesCommitChangedKey()
    {
        $params = json_encode(
            [
                'settings' => [
                    'commit_rules'        => [
                        'is_reference_mandatory' => true,
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
                            ],
                            'users'       => [],
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingImmutableTagsWhiteListKey()
    {
        $params = json_encode(
            [
                'settings' => [
                    'access_file'         => "[/]\r\n* = rw\r\n@members = rw",
                    'immutable_tags'      => [
                        'paths' => [],
                    ],
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                            ],
                            'users'       => [],
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingUsersKey()
    {
        $params = json_encode(
            [
                'settings' => [
                    'access_file'         => "[/]\r\n* = rw\r\n@members = rw",
                    'immutable_tags'      => [
                        'paths' => [],
                    ],
                    'email_notifications' => [
                        [
                            'path'        => '/tags',
                            'emails'      => [
                                'project-announce@list.example.com',
                            ],
                            'user_groups' => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingUserGroupsKey()
    {
        $params = json_encode(
            [
                'settings' => [
                    'access_file'         => "[/]\r\n* = rw\r\n@members = rw",
                    'immutable_tags'      => [
                        'paths' => [],
                    ],
                    'email_notifications' => [
                        [
                            'path'   => '/tags',
                            'emails' => [
                                'project-announce@list.example.com',
                            ],
                            'users'  => [],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'svn')->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 400);
    }
}
