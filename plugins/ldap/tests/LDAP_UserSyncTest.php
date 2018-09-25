<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

// Partial because of inheritance issues
Mock::generatePartial('LDAPResult', 'LDAPResultTestVersion', array('getEmail', 'getCommonName', 'get'));
Mock::generatePartial('PFUser', 'User4LDAPUserSync', array('getRealName', 'getEmail', 'getStatus', 'setRealName', 'setEmail', 'setStatus'));

// Override instance to test the right file
class LDAP_UserSyncTestVersion extends LDAP_UserSync {
    public static function instance() {
        include_once dirname(__FILE__).'/../site-content/en_US/synchronize_user.txt';
        return new LDAPPluginCustomUserSync();
    }
}

class LDAP_UserSyncTest extends TuleapTestCase {

    function testNoUpdateWhenNoDifference() {
        $user = new User4LDAPUserSync($this);
        $user->setReturnValue('getRealName', 'toto');
        $user->setReturnValue('getEmail',    'toto');
        $user->expectNever('setRealName');
        $user->expectNever('setEmail');
        
        $lr = new LDAPResultTestVersion($this);
        $lr->setReturnValue('getCommonName', 'toto');
        $lr->setReturnValue('getEmail',      'toto');
        
        $sync = new LDAP_UserSync();
        $sync->sync($user, $lr);
    }
    
    function testUserUpdateEmailIfLdapDoesntMatch() {
        $user = new User4LDAPUserSync($this);
        $user->setReturnValue('getRealName', 'toto');
        $user->setReturnValue('getEmail',    'toto');
        $user->expectNever('setRealName');
        $user->expectOnce('setEmail', array('foobar'));
        
        $lr = new LDAPResultTestVersion($this);
        $lr->setReturnValue('getCommonName', 'toto');
        $lr->setReturnValue('getEmail',      'foobar');
        
        $sync = new LDAP_UserSync();
        $sync->sync($user, $lr);
    }
    

    function testUserUpdateRealnameIfLdapDoesntMatch() {
        $user = new User4LDAPUserSync($this);
        $user->setReturnValue('getRealName', 'toto');
        $user->setReturnValue('getEmail',    'toto');
        $user->expectOnce('setRealName', array('foobar'));
        $user->expectNever('setEmail');
        
        $lr = new LDAPResultTestVersion($this);
        $lr->setReturnValue('getCommonName', 'foobar');
        $lr->setReturnValue('getEmail',      'toto');
        
        $sync = new LDAP_UserSync();
        $sync->sync($user, $lr);
    }

    function testChangeUserStatusWithDedicatedCode() {
        $user = new User4LDAPUserSync($this);
        $user->setReturnValue('getRealName', 'toto');
        $user->setReturnValue('getEmail',    'toto');
        $user->setReturnValue('getStatus',   PFUser::STATUS_ACTIVE);
        $user->expectNever('setRealName');
        $user->expectNever('setEmail');
        $user->expectOnce('setStatus', array(PFUser::STATUS_RESTRICTED));
        
        $lr = new LDAPResultTestVersion($this);
        $lr->setReturnValue('getCommonName', 'toto');
        $lr->setReturnValue('getEmail',      'toto');
        $lr->setReturnValue('get',           'contractor', array('employeetype'));
        

        $sync = LDAP_UserSyncTestVersion::instance();
        $sync->sync($user, $lr);
    }
}
?>