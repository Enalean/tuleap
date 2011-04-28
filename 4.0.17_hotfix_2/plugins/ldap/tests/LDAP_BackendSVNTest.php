<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/LDAP_BackendSVN.class.php';

Mock::generatePartial('LDAP_BackendSVN', 'LDAP_BackendSVNTestVersion', array('getLDAPGroupDao', 'getLDAP'));
Mock::generate('LDAP');

class LDAP_BackendSVNTest extends UnitTestCase {
    
    function __construct($name = 'LDAP_BackendSVN test') {
        parent::__construct($name);
    }

    function testGetLDAPServersUrlWithOneServer() {
        $ldap = new MockLDAP($this);
        $ldap->setReturnValue('getLDAPParam', 'ldap://ldap.codendi.com', array('server'));
        $ldap->setReturnValue('getLDAPParam', 'dc=codendi,dc=com', array('dn'));
        $ldapBackendSVN = new LDAP_BackendSVNTestVersion($this);
        $ldapBackendSVN->setReturnValue('getLDAP', $ldap);
        $this->assertEqual($ldapBackendSVN->getLDAPServersUrl(), 'ldap://ldap.codendi.com/dc=codendi,dc=com');
    }

    function testGetLDAPServersUrlWithTwoServers() {
        $ldap = new MockLDAP($this);
        $ldap->setReturnValue('getLDAPParam', 'ldap://ldap1.codendi.com, ldap://ldap2.codendi.com', array('server'));
        $ldap->setReturnValue('getLDAPParam', 'dc=codendi,dc=com', array('dn'));
        $ldapBackendSVN = new LDAP_BackendSVNTestVersion($this);
        $ldapBackendSVN->setReturnValue('getLDAP', $ldap);
        $this->assertEqual($ldapBackendSVN->getLDAPServersUrl(), 'ldap://ldap1.codendi.com ldap2.codendi.com/dc=codendi,dc=com');
    }
    
    function testGetLDAPServersUrlWithTwoServersLdaps() {
        $ldap = new MockLDAP($this);
        $ldap->setReturnValue('getLDAPParam', 'ldaps://ldap1.codendi.com, ldaps://ldap2.codendi.com', array('server'));
        $ldap->setReturnValue('getLDAPParam', 'dc=codendi,dc=com', array('dn'));
        $ldapBackendSVN = new LDAP_BackendSVNTestVersion($this);
        $ldapBackendSVN->setReturnValue('getLDAP', $ldap);
        $this->assertEqual($ldapBackendSVN->getLDAPServersUrl(), 'ldaps://ldap1.codendi.com ldap2.codendi.com/dc=codendi,dc=com');
    }
}
?>