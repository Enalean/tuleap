<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2009.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'bootstrap.php';

Mock::generatePartial('Docman_PermissionsManager', 'Docman_PermissionsManagerTestLock', array('getLockFactory', '_isUserDocmanAdmin', 'userCanManage'));
Mock::generate('PFUser');
Mock::generate('Docman_LockFactory');

class PermissionsManagerLockTest extends TuleapTestCase {
    private $user;
    private $docmanPm;

    public function setUp()
    {
        parent::setUp();
        $this->user = mock('PFUser');
        $this->user->setReturnValue('getId', 1234);
        $this->itemId = 1848;
        $this->docmanPm = new Docman_PermissionsManagerTestLock($this);
    }

    function testItemIsNotLocked() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        // user cannot manage
        $this->docmanPm->setReturnValue('userCanManage', false);

        $lockFactory = new MockDocman_LockFactory($this);
        $lockFactory->setReturnValue('itemIsLockedByItemId', false);
        $this->docmanPm->setReturnValue('getLockFactory', $lockFactory);

        $this->assertFalse($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }

    function testItemIsLockedBySomeoneelse() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        // user cannot manage
        $this->docmanPm->setReturnValue('userCanManage', false);

        $lockFactory = new MockDocman_LockFactory($this);
        $lockFactory->setReturnValue('itemIsLockedByItemId', true);
        $lockFactory->setReturnValue('userIsLockerByItemId', false);
        $this->docmanPm->setReturnValue('getLockFactory', $lockFactory);

        $this->assertTrue($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }

    function testItemIsLockedBySomeoneelseButUserCanManage() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        // user cannot manage
        $this->docmanPm->setReturnValue('userCanManage', true);

        $lockFactory = new MockDocman_LockFactory($this);
        $lockFactory->setReturnValue('itemIsLockedByItemId', true);
        $lockFactory->setReturnValue('userIsLockerByItemId', false);
        $this->docmanPm->setReturnValue('getLockFactory', $lockFactory);

        $this->assertFalse($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }

    function testItemIsLockedByOwner() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        // user cannot manage
        $this->docmanPm->setReturnValue('userCanManage', false);

        $lockFactory = new MockDocman_LockFactory($this);
        $lockFactory->setReturnValue('itemIsLockedByItemId', true);
        $lockFactory->setReturnValue('userIsLockerByItemId', true);
        $this->docmanPm->setReturnValue('getLockFactory', $lockFactory);

        $this->assertFalse($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }
}
