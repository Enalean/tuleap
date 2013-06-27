<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 * 
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../include/LDAP.class.php';
require_once dirname(__FILE__).'/../include/LDAP_SVN_Apache.class.php';
require_once dirname(__FILE__).'/../include/LDAP_ProjectManager.class.php';

class LDAP_SVN_ApacheTest extends TuleapTestCase {
    private $ldap;

    public function setUp() {
        parent::setUp();
        $this->ldap = mock('LDAP');
        stub($this->ldap)->getLDAPParam('dn')->returns('dc=tuleap,dc=com');

        $this->apache  = new LDAP_SVN_Apache($this->ldap, mock('LDAP_ProjectManager'), array());
    }

    function testGetLDAPServersUrlWithOneServer() {
        stub($this->ldap)->getLDAPParam('server')->returns('ldap://ldap.tuleap.com');

        $this->assertEqual($this->apache->getLDAPServersUrl(), 'ldap://ldap.tuleap.com/dc=tuleap,dc=com');
    }

    function testGetLDAPServersUrlWithTwoServers() {
        stub($this->ldap)->getLDAPParam('server')->returns('ldap://ldap1.tuleap.com, ldap://ldap2.tuleap.com');

        $this->assertEqual($this->apache->getLDAPServersUrl(),  'ldap://ldap1.tuleap.com ldap2.tuleap.com/dc=tuleap,dc=com');
    }
    
    function testGetLDAPServersUrlWithTwoServersLdaps() {
        stub($this->ldap)->getLDAPParam('server')->returns('ldaps://ldap1.tuleap.com, ldaps://ldap2.tuleap.com');

        $this->assertEqual($this->apache->getLDAPServersUrl(),  'ldaps://ldap1.tuleap.com ldap2.tuleap.com/dc=tuleap,dc=com');
    }

    function itIncludesBindDnAndPasswordIfAny() {
        $dn       = 'eduid=1234,ou=people,dc=tuleap,dc=com';
        $password = 'welcome0';
        stub($this->ldap)->getLDAPParam('bind_dn')->returns($dn);
        stub($this->ldap)->getLDAPParam('bind_passwd')->returns($password);

        $conf = $this->apache->getProjectAuthentication(array('group_name' => "Plop"));
        $this->assertPattern("/AuthLDAPBindDN \"$dn\"/", $conf);
        $this->assertPattern("/AuthLDAPBindPassword \"$password\"/", $conf);
    }

    function itDoesntIncludeSpecificThingsIfNoBindDn() {
        $conf = $this->apache->getProjectAuthentication(array('group_name' => "Plop"));
        $this->assertNoPattern("/AuthLDAPBindDN/", $conf);
        $this->assertNoPattern("/AuthLDAPBindPassword/", $conf);
    }

    function itDoesntIncludeSpecificThingsIfBindDnIsEmpty() {
        stub($this->ldap)->getLDAPParam('bind_dn')->returns("");
        $conf = $this->apache->getProjectAuthentication(array('group_name' => "Plop"));
        $this->assertNoPattern("/AuthLDAPBindDN/", $conf);
        $this->assertNoPattern("/AuthLDAPBindPassword/", $conf);
    }

    function itAddsTheUidWhenItsNotDefaultForActiveDirectory() {
        stub($this->ldap)->getLDAPParam('server')->returns('ldap://ldap.tuleap.com');
        stub($this->ldap)->getLDAPParam('uid')->returns('sAMAccountName');

        $this->assertEqual($this->apache->getLDAPServersUrl(), 'ldap://ldap.tuleap.com/dc=tuleap,dc=com?sAMAccountName');
    }
}
?>
