<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/system_event/include/SystemEvent_COMPUTE_MD5SUM.class.php');
Mock::generatePartial('SystemEvent_COMPUTE_MD5SUM', 'SystemEvent_COMPUTE_MD5SUM_TestVersion', array('getUser', 'done', 'computeFRSMd5Sum', 'updateDB'));

require_once('common/user/User.class.php');
Mock::generate('User');

require_once('common/frs/FRSFileFactory.class.php');
Mock::generate('FRSFileFactory');


class SystemEvent_COMPUTE_MD5SUM_Test extends UnitTestCase {

    public function __construct($name = 'SystemEvent_COMPUTE_MD5SUM test') {
        parent::__construct($name);
    }

    /**
     * Compute md5sum
     */
    public function computeMd5sumSucceed() {
        $evt = new SystemEvent_COMPUTE_MD5SUM_TestVersion($this);
        $evt->SystemEvent('1', SystemEvent::TYPE_COMPUTE_MD5SUM, '/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump'.SystemEvent::PARAMETER_SEPARATOR.'100012', SystemEvent::PRIORITY_MEDIUM, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The file
        $fileFactory = new MockFRSFileFactory($this);
        $file = new MockFRSFile($this);
        $fileFactory->setReturnValue('getFRSFileFromDb', $file, array('100012'));

        $evt->setReturnValue('computeFRSMd5Sum', 'd41d8cd98f00b204e9800998ecf8427e');

        // DB
        $evt->setReturnValue('updateDB', true);

        // Expect everything went OK
        $evt->expectOnce('done');

        // Launch the event
        $this->assertTrue($evt->process());
    }

    public function computeMd5sumFailure() {
        $evt = new SystemEvent_COMPUTE_MD5SUM_TestVersion($this);
        $evt->SystemEvent('1', SystemEvent::TYPE_COMPUTE_MD5SUM, '/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump'.SystemEvent::PARAMETER_SEPARATOR. '10013', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The file
        $fileFactory = new MockFRSFileFactory($this);
        $file = new MockFRSFile($this);
        $fileFactory->setReturnValue('getFRSFileFromDb', $file, array('100012'));

        $file->setReturnValue('getUserID', 142);

        $evt->setReturnValue('computeFRSMd5Sum', false);

        // The user
        $user = new MockUser($this);
        $user->setReturnValue('getEmail', 'mickey@codendi.com');
        $evt->setReturnValue('getUser', $user, array('142'));

        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Computing md5sum failed /i', $evt->getLog());

    }

    public function computeMd5sumUpdateDBFailure() {
        $evt = new SystemEvent_COMPUTE_MD5SUM_TestVersion($this);
        $evt->SystemEvent('1', SystemEvent::TYPE_COMPUTE_MD5SUM, '/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump'.SystemEvent::PARAMETER_SEPARATOR. '10013', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The file
        $fileFactory = new MockFRSFileFactory($this);
        $file = new MockFRSFile($this);
        $fileFactory->setReturnValue('getFRSFileFromDb', $file, array('100012'));

        $fileName = 'p2952_r10819/test.dump';
        $file->setReturnValue('getUserID', 142);
        $file->setReturnValue('getFileName', $fileName);

        $evt->setReturnValue('computeFRSMd5Sum', true);

        // DB
        $evt->setReturnValue('updateDB', false);


        // Expect everything went OK
        $evt->expectNever('done');
        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not update the computed checksum for file (Filename: '.$fileName.')/i', $evt->getLog());
    }
}
?>
