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

require_once(dirname(__FILE__).'/../include/HudsonTestResult.class.php');
Mock::generatePartial(
    'HudsonTestResult',
    'HudsonTestResultTestVersion',
    array('_getXMLObject', 'getHudsonControler', 'getIconsPath')
);

require_once(dirname(__FILE__).'/../include/hudson.class.php');
Mock::generate('hudson');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class HudsonTestResultTest extends UnitTestCase {
    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    function tearDown() {
        unset($GLOBALS['Language']);
    }
    
    function testMalformedURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $j = new HudsonJob("toto");
    }
    function testMissingSchemeURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $j = new HudsonJob("code4:8080/hudson/jobs/Codendi");
    }
    function testMissingHostURL() {
        $this->expectException('HudsonJobURLMalformedException');
	// See http://php.net/parse_url
        if (version_compare(PHP_VERSION, '5.3.3', '<')) {
            $this->expectError();
        }
        $j = new HudsonJob("http://");
    }
    
    function testSimpleJobTestResult() {
        
        $test_result_file = dirname(__FILE__).'/resources/testReport.xml';
        $xmldom = simplexml_load_file($test_result_file);
        
        $j = new HudsonTestResultTestVersion($this);
        $j->setReturnValue('_getXMLObject', $xmldom);
        $mh = new Mockhudson($this);
        $mh->setReturnValue('getIconsPath', '');
        $j->setReturnValue('getHudsonControler', $mh);
        $j->setReturnValue('getIconsPath', '');
        
        $j->HudsonTestResult("http://myCIserver/jobs/myCIjob/lastBuild/testReport/");
        
        $this->assertEqual($j->getFailCount(), 5);
        $this->assertEqual($j->getPassCount(), 416);
        $this->assertEqual($j->getSkipCount(), 3);
        $this->assertEqual($j->getTotalCount(), 424);
        
    }
        
}

?>