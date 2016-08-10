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
    'HudsonTestResult',
    'HudsonTestResultTestVersion',
    array('_getXMLObject', 'getHudsonControler', 'getIconsPath')
);
Mock::generate('hudson');
Mock::generate('BaseLanguage');

class HudsonTestResultTest extends TuleapTestCase {

    /** @var XML_Security */
    private $xml_security;
    /**
     * @var Http_Client
     */
    private $http_client;

    public function setUp() {
        parent::setUp();

        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $this->xml_security  = new XML_Security();
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
        $j = new HudsonTestResult("toto", $this->http_client);
    }
    function testMissingSchemeURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $j = new HudsonTestResult("code4:8080/hudson/jobs/Codendi", $this->http_client);
    }
    function testMissingHostURL() {
        $this->expectException('HudsonJobURLMalformedException');
	// See http://php.net/parse_url
        if (version_compare(PHP_VERSION, '5.3.3', '<')) {
            $this->expectError();
        }
        $j = new HudsonTestResult("http://", $this->http_client);
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
        
        $j->__construct("http://myCIserver/jobs/myCIjob/lastBuild/testReport/", $this->http_client);
        
        $this->assertEqual($j->getFailCount(), 5);
        $this->assertEqual($j->getPassCount(), 416);
        $this->assertEqual($j->getSkipCount(), 3);
        $this->assertEqual($j->getTotalCount(), 424);
    }
        
}
