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

require_once 'common/svn/SVN_Apache_Auth_Factory.class.php';

Mock::generate('EventManager');

class SVN_Apache_ModStuff extends SVN_Apache {
    function getProjectAuthentication($row) {
    }
}

class SVN_Apache_Auth_FactoryTestEventManager extends EventManager {
    function processEvent($event, $params) {
        $params['svn_apache_auth'] = new SVN_Apache_ModStuff($params['project_info']);
    }
}

class SVN_Apache_Auth_FactoryTest extends UnitTestCase {
    
    function setUp() {
        Config::store();
    }
    
    function tearDown() {
        Config::restore();
    }
    
    private function GivenAAuthFactoryWithoutAnyPlugin() {
        $factory = TestHelper::getPartialMock('SVN_Apache_Auth_Factory', array('getEventManager'));
        $factory->setReturnValue('getEventManager', new MockEventManager());
        return $factory;
    }
    
    function testFactoryShouldReturnModMysqlByDefault() {
        $projectInfo = array();
        $factory     = $this->GivenAAuthFactoryWithoutAnyPlugin();
        $this->assertIsA($factory->get($projectInfo), 'SVN_Apache_ModMysql');
    }
    
    function testFactoryShouldReturnModPerlIfPlatformConfiguredWithModPerl() {
        $projectInfo = array();
        Config::set(BackendSVN::CONFIG_SVN_AUTH_KEY, BackendSVN::CONFIG_SVN_AUTH_PERL);
        $factory = $this->GivenAAuthFactoryWithoutAnyPlugin();
        $this->assertIsA($factory->get($projectInfo), 'SVN_Apache_ModPerl');
    }
    
    private function GivenAAuthFactoryWithStuffPlugin() {
        $factory = TestHelper::getPartialMock('SVN_Apache_Auth_Factory', array('getEventManager'));
        $factory->setReturnValue('getEventManager', new SVN_Apache_Auth_FactoryTestEventManager());
        return $factory;
    }
    
    function testFactoryShouldReturnModStuffIfStuffAuthIsConfiguredForThisProject() {
        $projectInfo = array();
        $factory     = $this->GivenAAuthFactoryWithStuffPlugin();
        $this->assertIsA($factory->get($projectInfo), 'SVN_Apache_ModStuff');
    }
    
    function testFactoryShouldReturnModStuffIfStuffAuthIsConfiguredForThisProjectAndDefaultConfigIsModPerl() {
        $projectInfo = array();
        Config::set(BackendSVN::CONFIG_SVN_AUTH_KEY, BackendSVN::CONFIG_SVN_AUTH_PERL);
        $factory     = $this->GivenAAuthFactoryWithStuffPlugin();
        $this->assertIsA($factory->get($projectInfo), 'SVN_Apache_ModStuff');
    }
}

?>
