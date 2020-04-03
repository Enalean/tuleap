<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
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
use SOAPBase;

/**
 * Automation of "File releases delete using CLI"
 * @group FRSTest
 */
class FRSReleaseDeleteUsingCLITest extends SOAPBase
{
    private $content = 'Content of the file';

    // See database_initvalues
    private $type_text     = 8001;
    private $processor_any = 8000;

    private $session_hash;

    public function setUp(): void
    {
        parent::setUp();

        $_SERVER['SERVER_NAME'] = $this->server_name;
        $_SERVER['SERVER_PORT'] = $this->server_port;
        $_SERVER['SCRIPT_NAME'] = $this->base_wsdl;

        $this->session_hash = $this->getSessionHash();
    }

    public function tearDown(): void
    {
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['SCRIPT_NAME']);

        parent::tearDown();
    }

    public function testAddPackage()
    {
        $package_id = $this->soap_base->addPackage(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            'Package from SOAP',
            1,
            0,
            false
        );

        $this->assertTrue($package_id > 0);

        return $package_id;
    }

    /**
     * @depends testAddPackage
     */
    public function testAddRelease($package_id)
    {
        $release_id = $this->soap_base->addRelease(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $package_id,
            'Release from SOAP',
            'Release notes',
            'Changelog',
            1,
            time()
        );

        $this->assertTrue($release_id > 0);

        return $release_id;
    }

    /**
     * @depends testAddPackage
     * @depends testAddRelease
     */
    public function testThatNonEmptyPackageCannotBeDeleted($package_id)
    {
        $deleted_packages = $this->soap_base->deleteEmptyPackage(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $package_id
        );

        $this->assertEmpty($deleted_packages);
    }

    /**
     * @depends testAddRelease
     */
    public function testThatAllNonEmptyPackagesCannotBeDeleted()
    {
        $package_id       = 0;
        $cleanup_all      = true;
        $deleted_packages = $this->soap_base->deleteEmptyPackage(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $package_id,
            $cleanup_all
        );

        $this->assertEmpty($deleted_packages);
    }

    /**
     * @depends testAddPackage
     * @depends testAddRelease
     */
    public function testAddFile($package_id, $release_id)
    {
        $file_id = $this->soap_base->addFile(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $package_id,
            $release_id,
            'readme.txt',
            base64_encode($this->content),
            $this->type_text,
            $this->processor_any,
            md5($this->content),
            ''
        );

        $this->assertTrue($file_id > 0);

        return $file_id;
    }

    /**
     * @depends testAddPackage
     * @depends testAddRelease
     * @depends testAddFile
     */
    public function testThatNonEmptyReleaseCannotBeDeleted($package_id, $release_id)
    {
        $deleted_releases = $this->soap_base->deleteEmptyRelease(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $package_id,
            $release_id
        );

        $this->assertEmpty($deleted_releases);
    }

    /**
     * @depends testAddPackage
     * @depends testAddRelease
     * @depends testAddFile
     */
    public function testThatAllNonEmptyReleasesCannotBeDeleted($package_id)
    {
        $release_id       = 0;
        $cleanup_all      = true;
        $deleted_releases = $this->soap_base->deleteEmptyRelease(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $package_id,
            $release_id,
            $cleanup_all
        );

        $this->assertEmpty($deleted_releases);
    }

    /**
     * @depends testAddPackage
     * @depends testAddRelease
     * @depends testAddFile
     */
    public function testDeleteFile($package_id, $release_id, $file_id)
    {
        $this->assertTrue($this->soap_base->deleteFile(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $package_id,
            $release_id,
            $file_id
        ));
    }

    /**
     * @depends testAddPackage
     * @depends testAddRelease
     * @depends testDeleteFile
     */
    public function testDeleteRelease($package_id, $release_id)
    {
        $deleted_releases = $this->soap_base->deleteEmptyRelease(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $package_id,
            $release_id
        );

        $this->assertEquals(1, count($deleted_releases));
        $this->assertEquals($release_id, $deleted_releases[0]->release_id);
    }

    /**
     * @depends testAddPackage
     * @depends testDeleteRelease
     */
    public function testDeletePackage($package_id)
    {
        $deleted_packages = $this->soap_base->deleteEmptyPackage(
            $this->session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $package_id
        );

        $this->assertEquals(1, count($deleted_packages));
        $this->assertEquals($package_id, $deleted_packages[0]->package_id);
    }
}
