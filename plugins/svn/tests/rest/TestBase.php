<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
use RestBase;

/**
 * @group SVNTests
 */
class TestBase extends RestBase
{
    const PROJECT_NAME = 'svn-plugin-test';

    /**
     * @var array
     */
    public $user_102;

    /**
     * @var array
     */
    public $user_103;

    /**
     * @var int
     */
    protected $svn_project_id;

    public function setUp()
    {
        parent::setUp();

        $this->svn_project_id = $this->getProjectId(self::PROJECT_NAME);

        $this->user_102 = array(
            "id"           => 102,
            "uri"          => "users/102",
            "user_url"     => "/users/rest_api_tester_1",
            "real_name"    => "Test User 1",
            "display_name" => "Test User 1 (rest_api_tester_1)",
            "username"     => "rest_api_tester_1",
            "ldap_id"      => "tester1",
            "avatar_url"   => "http://localhost/themes/common/images/avatar_default.png",
            "is_anonymous" => false
        );

        $this->user_103 = array(
            "id"           => 103,
            "uri"          => "users/103",
            "user_url"     => "/users/rest_api_tester_2",
            "real_name"    => "",
            "display_name" => " (rest_api_tester_2)",
            "username"     => "rest_api_tester_2",
            "ldap_id"      => "",
            "avatar_url"   => "http://localhost/themes/common/images/avatar_default.png",
            "is_anonymous" => false
        );
    }

    protected function getResponse($request)
    {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }
}
