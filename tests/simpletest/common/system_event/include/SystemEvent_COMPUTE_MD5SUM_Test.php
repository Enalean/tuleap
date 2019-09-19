<?php
/**
 * Copyright (c) Enalean, 2012—2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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
 */

Mock::generatePartial('SystemEvent_COMPUTE_MD5SUM', 'SystemEvent_COMPUTE_MD5SUM_TestVersion', array('getUser','getFileFactory', 'done', 'computeFRSMd5Sum', 'compareMd5Checksums', 'sendNotificationMail', 'updateDB'));

Mock::generate('PFUser');

Mock::generate('FRSFile');

class SystemEvent_COMPUTE_MD5SUM_Test extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['sys_name'] = 'Codendi';
        $GLOBALS['sys_noreply'] = '"Codendi" <noreply@codendi.org>';
    }

    public function tearDown()
    {
        unset($GLOBALS['sys_name']);
        unset($GLOBALS['sys_noreply']);
        parent::tearDown();
    }

    /**
     * Compute md5sum
     */
    public function testComputeMd5sumSucceed()
    {
        $evt = new SystemEvent_COMPUTE_MD5SUM_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_COMPUTE_MD5SUM, SystemEvent::OWNER_ROOT, '100012', SystemEvent::PRIORITY_MEDIUM, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The file
        $fileFactory = \Mockery::mock(FRSFileFactory::class);
        $file = new MockFRSFile($this);
        $evt->setReturnValue('getFileFactory', $fileFactory);
        $fileFactory->shouldReceive('getFRSFileFromDB')->with('100012')->andReturn($file);
        $file->setReturnValue('getFileLocation', '/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump');

        $evt->setReturnValue('computeFRSMd5Sum', 'd41d8cd98f00b204e9800998ecf8427e');

        // DB
        $evt->setReturnValue('updateDB', true);

        //Checksum comparison
        $evt->setReturnValue('compareMd5Checksums', true);
        // Expect everything went OK
        $evt->expectOnce('done');

        // Launch the event
        $this->assertTrue($evt->process());
    }

    public function testComputeMd5sumFailure()
    {
        $evt = new SystemEvent_COMPUTE_MD5SUM_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_COMPUTE_MD5SUM, SystemEvent::OWNER_ROOT, '100012', SystemEvent::PRIORITY_MEDIUM, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The file
        $fileFactory = \Mockery::mock(FRSFileFactory::class);
        $file = new MockFRSFile($this);
        $evt->setReturnValue('getFileFactory', $fileFactory);
        $fileFactory->shouldReceive('getFRSFileFromDB')->with('100012')->andReturn($file);
        $file->setReturnValue('getFileLocation', '/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump');
        $file->setReturnValue('getUserID', 142);

        $evt->setReturnValue('computeFRSMd5Sum', false);

        // The user
        $user = mock('PFUser');
        $user->setReturnValue('getEmail', 'mickey@codendi.org');
        $evt->setReturnValue('getUser', $user);
        $evt->setReturnValue('sendNotificationMail', false);

        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not send mail to inform user that computing md5sum failed/i', $evt->getLog());
    }

    public function testComputeMd5sumUpdateDBFailure()
    {
        $evt = new SystemEvent_COMPUTE_MD5SUM_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_COMPUTE_MD5SUM, SystemEvent::OWNER_ROOT, '10013', SystemEvent::PRIORITY_MEDIUM, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The file
        $fileFactory = \Mockery::mock(FRSFileFactory::class);
        $file = new MockFRSFile($this);
        $evt->setReturnValue('getFileFactory', $fileFactory);
        $fileFactory->shouldReceive('getFRSFileFromDB')->andReturn($file);
        $file->setReturnValue('getFileLocation', '/var/lib/codendi/ftp/codendi/project_1/p1827_r6573/webkit-1.0.tar.gz');

        $evt->setReturnValue('computeFRSMd5Sum', 'd41d8cd98f00b204e9800998ecf8427e');

        // DB
        $evt->setReturnValue('updateDB', false);

        $evt->expectNever('done');
        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not update the computed checksum for file/i', $evt->getLog());
    }

    public function testComparisonMd5sumFailure()
    {
        $evt = new SystemEvent_COMPUTE_MD5SUM_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_COMPUTE_MD5SUM, SystemEvent::OWNER_ROOT, '100012', SystemEvent::PRIORITY_MEDIUM, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The file
        $fileFactory = \Mockery::mock(FRSFileFactory::class);
        $file = new MockFRSFile($this);
        $evt->setReturnValue('getFileFactory', $fileFactory);
        $fileFactory->shouldReceive('getFRSFileFromDB')->with('100012')->andReturn($file);
        $file->setReturnValue('getFileLocation', '/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump');
        $file->setReturnValue('getUserID', 142);

        $evt->setReturnValue('computeFRSMd5Sum', true);
        $evt->setReturnValue('updateDB', true);
        $evt->setReturnValue('compareMd5Checksums', false);

        // The user
        $user = mock('PFUser');
        $user->setReturnValue('getEmail', 'mickey@codendi.org');
        $evt->setReturnValue('getUser', $user);
        $evt->setReturnValue('sendNotificationMail', false);

        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not send mail to inform user that comparing md5sum failed/i', $evt->getLog());
    }
}
