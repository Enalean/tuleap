<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

require_once('bootstrap.php');

Mock::generatePartial(
    'HudsonBuild',
    'HudsonBuildTestVersion',
    array('_getXMLObject', 'getHudsonControler')
);
Mock::generate('hudson');
Mock::generate('BaseLanguage');

class HudsonBuildTest extends TuleapTestCase {

    /** @var XML_Security */
    private $xml_security;
    private $http_client;

    public function setUp() {
        parent::setUp();

        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
        $this->http_client = mock('Http_Client');
    }

    public function tearDown() {
        unset($GLOBALS['Language']);
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }
    
    function testMalformedURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $b = new HudsonBuild("toto", $this->http_client);
    }
    function testMissingSchemeURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $b = new HudsonBuild("code4:8080/hudson/jobs/Codendi", $this->http_client);
    }
    function testMissingHostURL() {
        $this->expectException('HudsonJobURLMalformedException');
        // See http://php.net/parse_url
        if (version_compare(PHP_VERSION, '5.3.3', '<')) {
            $this->expectError();
        }
        $b = new HudsonBuild("http://", $this->http_client);
    }
    
    function testSimpleJobBuild() {
        
        $build_file = dirname(__FILE__).'/resources/jobbuild.xml';
        $xmldom = simplexml_load_file($build_file);
        
        $h = new Mockhudson($this);
        
        $b = new HudsonBuildTestVersion($this);
        $b->setReturnValue('_getXMLObject', $xmldom);
        $b->setReturnValue('getHudsonControler', $h);
        
        $b->__construct("http://myCIserver/jobs/myCIjob/lastBuild/", $this->http_client);
        
        $this->assertEqual($b->getBuildStyle(), "freeStyleBuild");
        $this->assertFalse($b->isBuilding());
        $this->assertEqual($b->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/87/");
        $this->assertEqual($b->getResult(), "UNSTABLE");
        $this->assertEqual($b->getNumber(), 87);
        $this->assertEqual($b->getDuration(), 359231);
        $this->assertEqual($b->getTimestamp(), 1230051671000);
        
    }
        
}
