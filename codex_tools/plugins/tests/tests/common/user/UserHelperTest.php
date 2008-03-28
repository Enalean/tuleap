<?php

require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');
require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/user/UserHelper.class.php');
Mock::generatePartial('UserHelper', 'UserHelperTestVersion', array('_getCurrentUserUsernameDisplayPreference', '_getUserManager'));


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 *
 * Tests the class User
 */
class UserHelperTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function UserHelperTest($name = 'User Manager test') {
        $this->UnitTestCase($name);
    }
    
    function testGetDisplayName() {
        $uh =& new UserHelperTestVersion($this);
        $uh->setReturnValueAt(0, '_getCurrentUserUsernameDisplayPreference', 1);
        $uh->setReturnValueAt(1, '_getCurrentUserUsernameDisplayPreference', 2);
        $uh->setReturnValueAt(2, '_getCurrentUserUsernameDisplayPreference', 3);
        $uh->setReturnValueAt(3, '_getCurrentUserUsernameDisplayPreference', 4);
        $uh->setReturnValueAt(4, '_getCurrentUserUsernameDisplayPreference', 666);
        
        $uh->UserHelper();
        $this->assertEqual("user_name (realname)", $uh->getDisplayName("user_name", "realname"));
        
        $uh->UserHelper();
        $this->assertEqual("user_name", $uh->getDisplayName("user_name", "realname"));
        
        $uh->UserHelper();
        $this->assertEqual("realname", $uh->getDisplayName("user_name", "realname"));
        
        $uh->UserHelper();
        $this->assertEqual("realname (user_name)", $uh->getDisplayName("user_name", "realname"));
        
        $uh->UserHelper();
        $this->assertEqual("realname (user_name)", $uh->getDisplayName("user_name", "realname"));
    }
    
    function testGetDisplayNameFromUser() {
        $user =& new MockUser($this);
        $user->setReturnValue('getUserName', 'user_name');
        $user->setReturnValue('getRealName', 'realname');
        
        
        $uh =& new UserHelperTestVersion($this);
        $uh->setReturnValue('_getCurrentUserUsernameDisplayPreference', 1);
        $uh->UserHelper();
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUser($user));
    }
    
    function testGetDisplayNameFromUserId() {
        $user =& new MockUser($this);
        $user->setReturnValue('getUserName', 'user_name');
        $user->setReturnValue('getRealName', 'realname');
        
        $um =& new MockUserManager();
        $um->setReturnReference('getUserById', $user, array(123));
        
        $uh =& new UserHelperTestVersion($this);
        $uh->setReturnValue('_getCurrentUserUsernameDisplayPreference', 1);
        $uh->setReturnValue('_getUserManager', $um);
        $uh->UserHelper();
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUserId(123));
    }
    
    function testGetDisplayNameFromUserName() {
        $user =& new MockUser($this);
        $user->setReturnValue('getUserName', 'user_name');
        $user->setReturnValue('getRealName', 'realname');
        
        $um =& new MockUserManager();
        $um->setReturnReference('getUserByUserName', $user, array('user_name'));
        
        $uh =& new UserHelperTestVersion($this);
        $uh->setReturnValue('_getCurrentUserUsernameDisplayPreference', 1);
        $uh->setReturnValue('_getUserManager', $um);
        $uh->UserHelper();
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUserName('user_name'));
    }
    
}
?>
