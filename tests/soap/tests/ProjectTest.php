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

namespace Tuleap\Tests\SOAP;

use SOAP_TestDataBuilder;
use TestDataBuilder;
use SOAPBase;

/**
 * @group ProjectTest
 */
class ProjectTest extends SOAPBase
{

    public function setUp()
    {
        parent::setUp();

        $_SERVER['SERVER_NAME'] = $this->server_name;
        $_SERVER['SERVER_PORT'] = $this->server_port;
        $_SERVER['SCRIPT_NAME'] = $this->base_wsdl;
    }

    public function tearDown()
    {
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['SCRIPT_NAME']);

        parent::tearDown();
    }

    public function testAddUserToProject()
    {
        $session_hash = $this->getSessionHash();

        $response = $this->soap_project->addProjectMember(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            TestDataBuilder::TEST_USER_2_NAME
        );

        $this->assertTrue($response);
    }

    /**
     * @depends testAddUserToProject
     */
    public function testAddUserToUserGroup()
    {
        $session_hash = $this->getSessionHash();

        $response = $this->soap_project->addUserToUGroup(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            TestDataBuilder::STATIC_UGROUP_1_ID,
            TestDataBuilder::TEST_USER_2_ID
        );

        $this->assertTrue($response);
    }

    /**
     * @depends testAddUserToUserGroup
     */
    public function testRemoveProjectMember()
    {
        $session_hash = $this->getSessionHash();

        $response = $this->soap_project->removeProjectMember(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            TestDataBuilder::TEST_USER_2_NAME
        );

        $this->assertTrue($response);
    }
}
