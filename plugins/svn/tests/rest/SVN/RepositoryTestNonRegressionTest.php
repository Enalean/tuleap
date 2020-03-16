<?php
/**
 *  Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

class RepositoryTestNonRegressionTest extends TestBase
{

    public const TULEAP_MAGIC_GROUP_ID_ANONYMOUS = 1;
    public const TULEAP_MAGIC_GROUP_ID_MEMBERS   = 3;

    public function testPOSTRepositoryWithMissingKey()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_05",
                "settings"   => array(
                    "commit_rules" => array(
                        "is_reference_mandatory" => true,
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithMissingPathKey()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_06",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'emails'      => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            'users'       => array(),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithMissingEmailsKey()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_07",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'users'       => array(),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithMissingUserGroupsKey()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_07",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'path'   => "/tags",
                            'users'  => array(),
                            "emails" => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithMissingUsersKey()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_08",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'emails'      => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            'path'        => "/tags",
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithEmailNotificationWithEmptyNotification()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_08",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'users'       => array(),
                            'emails'      => array(),
                            'path'        => "/tags",
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithDuplicatePath()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_07",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            'users'       => array(),
                            'user_groups' => array()
                        ),
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "test@list.example.com"
                            ),
                            'users'       => array(),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithNonExistingGroupId()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_07",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            'users'       => array(),
                            'user_groups' => array("1000")
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPOSTWithUGroupIdFromAnOtherProject()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_07",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            'users'       => array(),
                            'user_groups' => array("110_" . self::TULEAP_MAGIC_GROUP_ID_MEMBERS)
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithUnsupportedDynamicUGroupId()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_07",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            'users'       => array(),
                            'user_groups' => array($this->svn_project_id . "_" . self::TULEAP_MAGIC_GROUP_ID_ANONYMOUS)
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPOSTWithSupportedDynamicUGroupId()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_07",
                "settings"   => array(
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            'users'       => array(),
                            'user_groups' => array($this->svn_project_id . "_" . self::TULEAP_MAGIC_GROUP_ID_MEMBERS)
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 201);
    }

    public function testPUTRepositoryWithEmptyAccessFile()
    {
        $data = json_encode(
            array(
                'settings' => array(
                    'commit_rules'        => array(
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false
                    ),
                    "access_file"         => "",
                    "immutable_tags"      => array(
                        "paths"     => array(),
                        "whitelist" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            "users"       => array(),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->put('svn/1', null, $data));

        $repository = $response->json();

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals(
            $repository['settings']['commit_rules'],
            array(
                "is_reference_mandatory"           => true,
                "is_commit_message_change_allowed" => false
            )
        );
        $this->assertEquals(
            $repository['settings']['immutable_tags'],
            array(
                "paths"     => array(),
                "whitelist" => array(),
            )
        );
        $this->assertEquals(
            $repository['settings']['access_file'],
            ""
        );
    }

    public function testPUTRepositoryWithMissingCommitRulesKey()
    {
        $params = json_encode(
            array(
                'settings' => array(
                    "access_file"         => "[/]\r\n* = rw\r\n@members = rw",
                    "immutable_tags"      => array(
                        "paths"     => array(),
                        "whitelist" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com"
                            ),
                            "users"       => array(),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingImmutableTagKey()
    {
        $params = json_encode(
            array(
                'settings' => array(
                    'commit_rules'        => array(
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false
                    ),
                    "access_file"         => "[/]\r\n* = rw\r\n@members = rw",
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com"
                            ),
                            "users"       => array(),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingAccessFileKey()
    {
        $params = json_encode(
            array(
                'settings' => array(
                    'commit_rules'        => array(
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false
                    ),
                    "immutable_tags"      => array(
                        "paths"     => array(),
                        "whitelist" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com"
                            ),
                            "users"       => array(),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingEmailNotificationsKey()
    {
        $params = json_encode(
            array(
                'settings' => array(
                    'commit_rules'   => array(
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false
                    ),
                    "immutable_tags" => array(
                        "paths"     => array(),
                        "whitelist" => array()
                    ),
                    "access_file"    => "[/]\r\n* = rw\r\n@members = rw"
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingCommitRulesCommitChangedKey()
    {
        $params = json_encode(
            array(
                "settings" => array(
                    "commit_rules"        => array(
                        "is_reference_mandatory" => true,
                    ),
                    "access_file"         => "[/]\r\n* = rw\r\n@members = rw",
                    "immutable_tags"      => array(
                        "paths"     => array(),
                        "whitelist" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com"
                            ),
                            "users"       => array(),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingImmutableTagsWhiteListKey()
    {
        $params = json_encode(
            array(
                "settings" => array(
                    "access_file"         => "[/]\r\n* = rw\r\n@members = rw",
                    "immutable_tags"      => array(
                        "paths" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com"
                            ),
                            "users"       => array(),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingUsersKey()
    {
        $params = json_encode(
            array(
                "settings" => array(
                    "access_file"         => "[/]\r\n* = rw\r\n@members = rw",
                    "immutable_tags"      => array(
                        "paths" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'        => "/tags",
                            'emails'      => array(
                                "project-announce@list.example.com"
                            ),
                            'user_groups' => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingUserGroupsKey()
    {
        $params = json_encode(
            array(
                "settings" => array(
                    "access_file"         => "[/]\r\n* = rw\r\n@members = rw",
                    "immutable_tags"      => array(
                        "paths" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'   => "/tags",
                            'emails' => array(
                                "project-announce@list.example.com"
                            ),
                            'users'  => array()
                        )
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }
}
