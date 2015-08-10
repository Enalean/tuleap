<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group DocmanTest
 */
class DocmanTest extends SOAPBase {

    public function setUp() {
        parent::setUp();

        $_SERVER['SERVER_NAME'] = $this->server_name;
        $_SERVER['SERVER_PORT'] = $this->server_port;
        $_SERVER['SCRIPT_NAME'] = $this->base_wsdl;
    }

    public function tearDown() {
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['SCRIPT_NAME']);

        parent::tearDown();
    }

    public function testGetDocumentRootFolder() {
        $session_hash = $this->getSessionHash();

        $root_folder_id = $this->soap_base->getRootFolder(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID
        );

        $this->assertNotNull($root_folder_id);
        $this->assertTrue(is_int($root_folder_id));

        return $root_folder_id;
    }

    /**
     * @depends testGetDocumentRootFolder
     */
    public function testCreateFolder($root_folder_id) {
        $session_hash = $this->getSessionHash();

        $title       = 'My Folder';
        $description = 'My Folder';
        $ordering    = 'begin';
        $status      = 'approved';
        $permissions = array();
        $metadata    = array();
        $owner       = SOAP_TestDataBuilder::TEST_USER_1_NAME;
        $create_date = '1438953065';
        $update_date = '';

        $first_folder_id = $this->soap_base->createDocmanFolder(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $root_folder_id,
            $title,
            $description,
            $ordering,
            $status,
            $permissions,
            $metadata,
            $owner,
            $create_date,
            $update_date
        );

        $this->assertNotNull($first_folder_id);
        $this->assertTrue(is_int($first_folder_id));

        return $root_folder_id;
    }

    /**
     * @depends testCreateFolder
     */
    public function testCreateFolderWithSpaces($root_folder_id) {
        $session_hash = $this->getSessionHash();

        $title       = ' My second Folder ';
        $description = 'My Folder';
        $ordering    = 'begin';
        $status      = 'approved';
        $permissions = array();
        $metadata    = array();
        $owner       = SOAP_TestDataBuilder::TEST_USER_1_NAME;
        $create_date = '1438953065';
        $update_date = '';

        $second_folder_id = $this->soap_base->createDocmanFolder(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $root_folder_id,
            $title,
            $description,
            $ordering,
            $status,
            $permissions,
            $metadata,
            $owner,
            $create_date,
            $update_date
        );

        $this->assertNotNull($second_folder_id);
        $this->assertTrue(is_int($second_folder_id));

        return $root_folder_id;
    }

    /**
     * @depends testCreateFolderWithSpaces
     */
    public function testGetFirstFolder($root_folder_id) {
        $session_hash = $this->getSessionHash();

        $response = $this->soap_base->listFolder(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $root_folder_id
        );

        $this->assertEquals(count($response), 2);

        $this->assertEquals($response[0]->title, 'My second Folder');
        $this->assertEquals($response[1]->title, 'My Folder');
    }
}
