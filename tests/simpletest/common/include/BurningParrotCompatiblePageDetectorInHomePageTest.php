<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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
 *
 */

namespace Tuleap;

use Tuleap\Request\CurrentPage;
use User_ForgeUserGroupPermissionsManager;

class BurningParrotCompatiblePageDetectorInHomePageTest extends \TuleapTestCase
{
    private $dao;
    private $detector;

    public function setUp()
    {
        parent::setUp();

        $this->preserveServer('REQUEST_URI');
        $this->preserveServer('SERVER_NAME');
        \ForgeConfig::store();

        $forge_user_group_permissions_manager = mock(User_ForgeUserGroupPermissionsManager::class);

        $this->dao      = stub('Admin_Homepage_Dao')->isStandardHomepageUsed()->returns(true);
        $this->detector = new BurningParrotCompatiblePageDetector(
            new CurrentPage(),
            $this->dao,
            $forge_user_group_permissions_manager
        );
    }

    public function tearDown()
    {
        \ForgeConfig::restore();
        parent::tearDown();
    }

    public function itReturnsTrueWhenSlash()
    {
        \ForgeConfig::set('sys_default_domain', 'tuleap.example.com');
        $_SERVER['SERVER_NAME'] = 'tuleap.example.com';
        $_SERVER['REQUEST_URI'] = '/';

        $this->assertTrue($this->detector->isInHomepage());
    }

    public function itReturnsTrueWhenSlashIndex()
    {
        \ForgeConfig::set('sys_default_domain', 'tuleap.example.com');
        $_SERVER['SERVER_NAME'] = 'tuleap.example.com';
        $_SERVER['REQUEST_URI'] = '/index.php';

        $this->assertTrue($this->detector->isInHomepage());
    }


    public function itReturnsTrueWhenSlashIndexWithParams()
    {
        \ForgeConfig::set('sys_default_domain', 'tuleap.example.com');
        $_SERVER['SERVER_NAME'] = 'tuleap.example.com';
        $_SERVER['REQUEST_URI'] = '/index.php?foo=bar';

        $this->assertTrue($this->detector->isInHomepage());
    }

    public function itReturnsFalseWhenRunningOutsideTheBaseHostNames()
    {
        \ForgeConfig::set('sys_default_domain', 'tuleap.example.com');
        $_SERVER['SERVER_NAME'] = 'webdav.tuleap.example.com';
        $_SERVER['REQUEST_URI'] = '/';

        $this->assertFalse($this->detector->isInHomepage());
    }
}
