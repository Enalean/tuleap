<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/frs/FRSReleaseFactory.class.php');

Mock::generate('User');
Mock::generate('UserManager');
Mock::generatePartial('FRSReleaseFactory', 'FRSReleaseFactoryTestVersion', array('getUserManager'));

class FRSReleaseFactoryTest extends UnitTestCase {

    function testFileReleaseAdminHasAlwaysAccessToReleases() {
        // Values
        $group_id   = 12;
        $package_id = 34;
        $release_id = 56;
        $user_id    = 78;

        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', false);
        $user->setReturnValue('isMember', true, array($group_id, 'R2'));

        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array($user_id));
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanRead($group_id, $package_id, $release_id, $user_id));
    }

    function testProjectAdminHasAlwaysAccessToReleases() {
        // Values
        $group_id   = 12;
        $package_id = 34;
        $release_id = 56;
        $user_id    = 78;

        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', false);
        $user->setReturnValue('isMember', true, array($group_id, 'A'));

        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array($user_id));
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanRead($group_id, $package_id, $release_id, $user_id));
    }

    function testSiteAdminHasAlwaysAccessToReleases() {
        // Values
        $group_id   = 12;
        $package_id = 34;
        $release_id = 56;
        $user_id    = 78;

        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', true);

        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array($user_id));
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanRead($group_id, $package_id, $release_id, $user_id));
    }

}

?>
