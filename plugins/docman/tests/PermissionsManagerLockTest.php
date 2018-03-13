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

class PermissionsManagerLockTest extends TuleapTestCase {
    private $user;
    private $docmanPm;

    public function setUp()
    {
        parent::setUp();
        $this->user = \Mockery::spy(PFUser::class);
        $this->user->allows(['getId' => 1234]);
        $this->itemId = 1848;
        $this->docmanPm = \Mockery::mock(Docman_PermissionsManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    function testItemIsNotLocked() {
        // user is not docman admin
        $this->docmanPm->allows(['_isUserDocmanAdmin' => false]);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        // user cannot manage
        $this->docmanPm->allows(['userCanManage' => false]);

        $lockFactory = \Mockery::spy(Docman_LockFactory::class);
        $lockFactory->allows(['itemIsLockedByItemId' => false]);
        $this->docmanPm->allows(['getLockFactory' => $lockFactory]);

        $this->assertFalse($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }

    function testItemIsLockedBySomeoneelse() {
        // user is not docman admin
        $this->docmanPm->allows(['_isUserDocmanAdmin' => false]);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        // user cannot manage
        $this->docmanPm->allows(['userCanManage' => false]);

        $lockFactory = \Mockery::spy(Docman_LockFactory::class);
        $lockFactory->allows(['itemIsLockedByItemId' => true]);
        $lockFactory->allows(['userIsLockerByItemId' => false]);
        $this->docmanPm->allows(['getLockFactory' => $lockFactory]);

        $this->assertTrue($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }

    function testItemIsLockedBySomeoneelseButUserCanManage() {
        // user is not docman admin
        $this->docmanPm->allows(['_isUserDocmanAdmin' => false]);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        // user cannot manage
        $this->docmanPm->allows(['userCanManage' => true]);

        $lockFactory = \Mockery::spy(Docman_LockFactory::class);
        $lockFactory->allows(['itemIsLockedByItemId' => true]);
        $lockFactory->allows(['userIsLockerByItemId' => false]);
        $this->docmanPm->allows(['getLockFactory' => $lockFactory]);

        $this->assertFalse($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }

    function testItemIsLockedByOwner() {
        // user is not docman admin
        $this->docmanPm->allows(['_isUserDocmanAdmin' => false]);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        // user cannot manage
        $this->docmanPm->allows(['userCanManage' => false]);

        $lockFactory = \Mockery::spy(Docman_LockFactory::class);
        $lockFactory->allows(['itemIsLockedByItemId' => true]);
        $lockFactory->allows(['userIsLockerByItemId' => true]);
        $this->docmanPm->allows(['getLockFactory' => $lockFactory]);

        $this->assertFalse($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }
}
