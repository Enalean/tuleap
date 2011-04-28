<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2008.
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

require_once(dirname(__FILE__).'/../include/LDAP_UserManager.class.php');

Mock::generatePartial('LDAP_UserManager', 'LDAP_UserManagerGenerateLogin', array('getLoginFromString', 'userNameIsAvailable'));
if (!class_exists('MockInhLDAP')) {
    // Need a fake mock just to make the type checking happy
    Mock::generatePartial('LDAP', 'MockInhLDAP', array('search'));
}

class LDAP_UserManagerTest extends UnitTestCase {
    
    function __construct($name = 'LDAP_UserManager test') {
        parent::__construct($name);
    }
    
    function testGetLoginFromString() {
        $ldap = new MockInhLDAP($this);
        $lum = new LDAP_UserManager($ldap);
        
        $this->assertEqual($lum->getLoginFromString('coincoin'), 'coincoin');
        
        $this->assertEqual($lum->getLoginFromString('coin coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin.coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin:coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin;coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin,coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin?coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin%coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin^coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin*coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin(coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin)coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin{coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin}coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin[coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin]coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin<coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin>coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin+coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin=coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin$coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin\ coin'), 'coin_coin');
        
        $this->assertEqual($lum->getLoginFromString("coincoin'"), 'coincoin');
        $this->assertEqual($lum->getLoginFromString('coincoin"'), 'coincoin');
        $this->assertEqual($lum->getLoginFromString('coin/coin'), 'coincoin');
        
        // Accent test
        $this->assertEqual($lum->getLoginFromString('coiné'), 'coine');

        // getLoginFromString only accept utf8 strings.
        //$this->assertEqual($lum->getLoginFromString(utf8_decode('coiné')), 'coine');
    }
    
    function testGenerateLoginNotAlreadyUsed() {
        $lum = new LDAP_UserManagerGenerateLogin($this);
        
        $lum->setReturnValue('getLoginFromString', 'john');
        $lum->setReturnValue('userNameIsAvailable', true);
        
        $this->assertEqual($lum->generateLogin('john'), 'john');
    }
    
    function testGenerateLoginAlreadyUsed() {
        $lum = new LDAP_UserManagerGenerateLogin($this);
        
        $lum->setReturnValue('getLoginFromString', 'john');
        $lum->setReturnValueAt(0, 'userNameIsAvailable', false);
        $lum->setReturnValueAt(1, 'userNameIsAvailable', true);
        
        $this->assertEqual($lum->generateLogin('john'), 'john2');
    }
}
?>