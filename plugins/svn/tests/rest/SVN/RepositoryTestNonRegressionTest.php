<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class RepositoryTestNonRegressionTest extends TestBase
{
    public function testPOSTRepositoryWithMissingKey()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository_05",
                "settings"   => array(
                    "commit_rules"   => array(
                        "is_reference_mandatory" => true,
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
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
                            'emails' => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            'users' => array()
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
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
                            'path'  => "/tags",
                            'users' => array()
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
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
                            'emails' => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            'path' => "/tags"
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
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
                            'users' => array(),
                            'emails' => array(),
                            'path' => "/tags"
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
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
                            'path'   => "/tags",
                            'emails' => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            )
                        ),
                        array(
                            'path'   => "/tags",
                            'emails' => array(
                                "test@list.example.com"
                            )
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithEmptyAccessFile()
    {
        $data = json_encode(
            array(
                'settings' => array(
                    'commit_rules'   => array(
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false
                    ),
                    "access_file"    => "",
                    "immutable_tags" => array(
                        "paths"     => array(),
                        "whitelist" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'   => "/tags",
                            'emails' => array(
                                "project-announce@list.example.com",
                                "project-devel@lists.example.com"
                            ),
                            "users" => array()
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
                    "access_file"    => "[/]\r\n* = rw\r\n@members = rw",
                    "immutable_tags" => array(
                        "paths"     => array(),
                        "whitelist" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'   => "/tags",
                            'emails' => array(
                                "project-announce@list.example.com"
                            )
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingImmutableTagKey()
    {
        $params = json_encode(
            array(
                'settings' => array(
                    'commit_rules' => array(
                        'is_reference_mandatory'           => true,
                        'is_commit_message_change_allowed' => false
                    ),
                    "access_file"  => "[/]\r\n* = rw\r\n@members = rw",
                    "email_notifications" => array(
                        array(
                            'path'   => "/tags",
                            'emails' => array(
                                "project-announce@list.example.com"
                            )
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingAccessFileKey()
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
                    "email_notifications" => array(
                        array(
                            'path'   => "/tags",
                            'emails' => array(
                                "project-announce@list.example.com"
                            )
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
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
                    "access_file"  => "[/]\r\n* = rw\r\n@members = rw"
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingCommitRulesCommitChangedKey()
    {
        $params = json_encode(
            array(
                "settings" => array(
                    "commit_rules" => array(
                        "is_reference_mandatory" => true,
                    ),
                    "access_file"    => "[/]\r\n* = rw\r\n@members = rw",
                    "immutable_tags" => array(
                        "paths"     => array(),
                        "whitelist" => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'   => "/tags",
                            'emails' => array(
                                "project-announce@list.example.com"
                            )
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPUTRepositoryWithMissingImmutableTagsWhiteListKey()
    {
        $params = json_encode(
            array(
                "settings" => array(
                    "access_file"    => "[/]\r\n* = rw\r\n@members = rw",
                    "immutable_tags" => array(
                        "paths"     => array()
                    ),
                    "email_notifications" => array(
                        array(
                            'path'   => "/tags",
                            'emails' => array(
                                "project-announce@list.example.com"
                            )
                        )
                    )
                )
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
        $response = $this->getResponse($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 400);
    }
}
