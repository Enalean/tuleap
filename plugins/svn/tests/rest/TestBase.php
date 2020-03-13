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

use RestBase;

/**
 * @group SVNTests
 */
class TestBase extends RestBase
{
    public const PROJECT_NAME  = 'svn-plugin-test';
    public const UGROUP_NAME_1 = 'svn_ugroup_1';
    public const UGROUP_NAME_2 = 'svn_ugroup_2';

    protected $svn_domain = 'https://localhost';

    /**
     * @var array
     */
    public $user_102;

    /**
     * @var array
     */
    public $user_103;

    /**
     * @var array
     */
    public $user_group_101;

    /**
     * @var array
     */
    public $user_group_102;

    /**
     * @var int
     */
    public $user_group_2_id;

    /**
     * @var int
     */
    public $user_group_1_id;

    /**
     * @var int
     */
    protected $svn_project_id;

    public function setUp() : void
    {
        parent::setUp();

        $this->svn_project_id = $this->getProjectId(self::PROJECT_NAME);
        $user_groups          = $this->getUserGroupsByProjectId($this->svn_project_id);

        $this->user_group_1_id = $user_groups[self::UGROUP_NAME_1];
        $this->user_group_2_id = $user_groups[self::UGROUP_NAME_2];

        $this->user_102 = array(
            "id"           => 102,
            "uri"          => "users/102",
            "user_url"     => "/users/rest_api_tester_1",
            "real_name"    => "Test User 1",
            "display_name" => "Test User 1 (rest_api_tester_1)",
            "username"     => "rest_api_tester_1",
            "ldap_id"      => "tester1",
            "avatar_url"   => "https://localhost/themes/common/images/avatar_default.png",
            "is_anonymous" => false,
            "has_avatar"   => false
        );

        $this->user_103 = array(
            "id"           => 103,
            "uri"          => "users/103",
            "user_url"     => "/users/rest_api_tester_2",
            "real_name"    => "",
            "display_name" => " (rest_api_tester_2)",
            "username"     => "rest_api_tester_2",
            "ldap_id"      => "",
            "avatar_url"   => "https://localhost/themes/common/images/avatar_default.png",
            "is_anonymous" => false,
            "has_avatar"   => false
        );

        $this->user_group_101 = array(
            "id"         => $this->user_group_1_id,
            "uri"        => "user_groups/" . $this->user_group_1_id,
            "label"      => self::UGROUP_NAME_1,
            "users_uri"  => "user_groups/" . $this->user_group_1_id . "/users",
            "short_name" => self::UGROUP_NAME_1,
            "key"        => self::UGROUP_NAME_1,
        );

        $this->user_group_102 = array(
            "id"         => $this->user_group_2_id,
            "uri"        => "user_groups/" . $this->user_group_2_id,
            "label"      =>  self::UGROUP_NAME_2,
            "users_uri"  => "user_groups/" . $this->user_group_2_id . "/users",
            "short_name" => self::UGROUP_NAME_2,
            "key"        =>  self::UGROUP_NAME_2
        );

        if (isset($_ENV['TULEAP_HOST'])) {
            $this->svn_domain  = $_ENV['TULEAP_HOST'];
        }
    }
}
