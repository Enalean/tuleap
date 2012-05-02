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

require_once(dirname(__FILE__).'/../include/HudsonBuild.class.php');
Mock::generatePartial(
    'HudsonBuild',
    'HudsonBuildTestVersion',
    array('_getXMLObject', 'getHudsonControler')
);

require_once(dirname(__FILE__).'/../include/hudson.class.php');
Mock::generate('hudson');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class HudsonBuildTest extends UnitTestCase {
    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    function tearDown() {
        unset($GLOBALS['Language']);
    }
    
    function testMalformedURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $b = new HudsonBuild("toto");
    }
    function testMissingSchemeURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $b = new HudsonBuild("code4:8080/hudson/jobs/Codendi");
    }
    function testMissingHostURL() {
        $this->expectException('HudsonJobURLMalformedException');
        // See http://php.net/parse_url
        if (version_compare(PHP_VERSION, '5.3.3', '<')) {
            $this->expectError();
        }
        $b = new HudsonBuild("http://");
    }
    
    function testSimpleJobBuild() {
        
        $build_file = dirname(__FILE__).'/resources/jobbuild.xml';
        $xmldom = simplexml_load_file($build_file);
        
        $h = new Mockhudson($this);
        
        $b = new HudsonBuildTestVersion($this);
        $b->setReturnValue('_getXMLObject', $xmldom);
        $b->setReturnValue('getHudsonControler', $h);
        
        $b->HudsonBuild("http://myCIserver/jobs/myCIjob/lastBuild/");
        
        $this->assertEqual($b->getBuildStyle(), "freeStyleBuild");
        $this->assertFalse($b->isBuilding());
        $this->assertEqual($b->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/87/");
        $this->assertEqual($b->getResult(), "UNSTABLE");
        $this->assertEqual($b->getNumber(), 87);
        $this->assertEqual($b->getDuration(), 359231);
        $this->assertEqual($b->getTimestamp(), 1230051671000);
        
    }
        
}

?>