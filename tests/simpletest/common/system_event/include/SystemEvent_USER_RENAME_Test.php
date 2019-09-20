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

Mock::generatePartial('SystemEvent_USER_RENAME', 'SystemEvent_USER_RENAME_TestVersion', array('done', 'getUser', 'getBackend', 'updateDB'));

Mock::generate('PFUser');

Mock::generate('BackendSystem');

Mock::generate('BackendSVN');

Mock::generate('BackendCVS');

class SystemEvent_USER_RENAME_Test extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('homedir_prefix', '/tmp');
    }

    function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    /**
     * Rename user 142 'mickey' in 'tazmani'
     */
    public function testRenameOps()
    {
        $evt = new SystemEvent_USER_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_USER_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'tazmani', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The user
        $user = mock('PFUser');
        $user->setReturnValue('getUserName', 'mickey');
        $evt->setReturnValue('getUser', $user, array('142'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('userHomeExists', true);
        $backendSystem->setReturnValue('isUserNameAvailable', true);
        $backendSystem->setReturnValue('renameUserHomeDirectory', true);
        $backendSystem->expectOnce('renameUserHomeDirectory', array($user, 'tazmani'));
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('updateCVSWritersForGivenMember', true);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('updateSVNAccessForGivenMember', true);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // Expect everything went OK
        $evt->expectOnce('done');

        // Launch the event
        $this->assertTrue($evt->process());
    }

    public function testRenameUserRepositoryFailure()
    {
        $evt = new SystemEvent_USER_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_USER_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'tazmani', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The user
        $user = mock('PFUser');
        $user->setReturnValue('getUserName', 'mickey');
        $evt->setReturnValue('getUser', $user, array('142'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('userHomeExists', true);
        $backendSystem->setReturnValue('isUserNameAvailable', true);
        $backendSystem->setReturnValue('renameUserHomeDirectory', false);
        $backendSystem->expectOnce('renameUserHomeDirectory', array($user, 'tazmani'));
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('updateCVSWritersForGivenMember', true);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('updateSVNAccessForGivenMember', true);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // There is an error, the rename is not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not rename user home/i', $evt->getLog());
    }


    public function testUpdateCVSWritersFailure()
    {
        $evt = new SystemEvent_USER_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_USER_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'tazmani', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The user
        $user = mock('PFUser');
        $user->setReturnValue('getUserName', 'mickey');
        $evt->setReturnValue('getUser', $user, array('142'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('userHomeExists', true);
        $backendSystem->setReturnValue('isUserNameAvailable', true);
        $backendSystem->setReturnValue('renameUserHomeDirectory', true);
        $backendSystem->expectOnce('renameUserHomeDirectory', array($user, 'tazmani'));
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('updateCVSWritersForGivenMember', false);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('updateSVNAccessForGivenMember', true);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // There is an error, the rename is not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not update CVS writers for the user/i', $evt->getLog());
    }

    public function testUpdateSVNAccessFailure()
    {
        $evt = new SystemEvent_USER_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_USER_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'tazmani', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The user
        $user = mock('PFUser');
        $user->setReturnValue('getUserName', 'mickey');
        $evt->setReturnValue('getUser', $user, array('142'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('userHomeExists', true);
        $backendSystem->setReturnValue('isUserNameAvailable', true);
        $backendSystem->setReturnValue('renameUserHomeDirectory', true);
        $backendSystem->expectOnce('renameUserHomeDirectory', array($user, 'tazmani'));
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('updateCVSWritersForGivenMember', true);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('updateSVNAccessForGivenMember', false);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // There is an error, the rename is not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not update SVN access files for the user/i', $evt->getLog());
    }
}
