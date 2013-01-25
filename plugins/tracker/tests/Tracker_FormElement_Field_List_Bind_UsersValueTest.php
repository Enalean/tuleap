<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 require_once('bootstrap.php');
Mock::generatePartial(
    'Tracker_FormElement_Field_List_Bind_UsersValue', 
    'Tracker_FormElement_Field_List_Bind_UsersValueTestVersion', 
    array('getUserHelper', 'getUserManager', 'getId')
);

require_once('common/user/UserHelper.class.php');
Mock::generate('UserHelper');

require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');

require_once('common/user/User.class.php');
Mock::generate('User');

class Tracker_FormElement_Field_List_Bind_UsersValueTest extends UnitTestCase {
    
    public function testGetLabel() {
        $uh = new MockUserHelper();
        $uh->setReturnValue('getDisplayNameFromUserId', 'John Smith', array(123));
        
        $bv = new Tracker_FormElement_Field_List_Bind_UsersValueTestVersion();
        $bv->setReturnValue('getId', 123);
        $bv->setReturnReference('getUserHelper', $uh);
        
        $this->assertEqual($bv->getLabel(), 'John Smith');
    }
    
    public function testGetUser() {
        $u = new MockUser();
        
        $uh = new MockUserManager();
        $uh->setReturnValue('getUserById', $u, array(123));
        
        $bv = new Tracker_FormElement_Field_List_Bind_UsersValueTestVersion();
        $bv->setReturnValue('getId', 123);
        $bv->setReturnReference('getUserManager', $uh);
        
        $this->assertReference($bv->getUser(), $u);
    }
    
}

class Tracker_FormElement_Field_List_Bind_UsersValue_fetchJSONTest extends TuleapTestCase {

    public function itReturnsTheUserNameAsWell() {
        $value = new Tracker_FormElement_Field_List_Bind_UsersValue(12, 'neo', 'Thomas A. Anderson (neo)');
        $json = $value->fetchJSON();
        $this->assertEqual($json, array(
            'value'    => 'b12',
            'caption'  => 'Thomas A. Anderson (neo)',
            'username' => 'neo',
        ));
    }
}
?>
