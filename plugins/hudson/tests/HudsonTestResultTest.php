<?php

/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonTestResultTest : test the class HudsonTestResult
 */

require_once(dirname(__FILE__).'/../include/HudsonTestResult.class.php');
Mock::generatePartial(
    'HudsonTestResult',
    'HudsonTestResultTestVersion',
    array('_getXMLObject', 'getHudsonControler')
);

require_once(dirname(__FILE__).'/../include/hudson.class.php');
Mock::generate('hudson');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class HudsonTestResultTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function HudsonTestResultTest($name = 'HudsonTestResult test') {
        $this->UnitTestCase($name);
    }
    
    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    
    function testMalformedURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $this->expectError();
        $j = new HudsonJob("toto");
    }
    function testMissingSchemeURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $this->expectError();
        $j = new HudsonJob("code4:8080/hudson/jobs/Codendi");
    }
    function testMissingHostURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $this->expectError();
        $j = new HudsonJob("http://");
    }
    
    function testWrongXMLFile() {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<foo>
 <bar>1</bar>
 <bar>2</bar>
</foo>
XML;
        $xmldom = new SimpleXMLElement($xmlstr);
        
        $j = new HudsonJobTestVersion($this);
        $j->setReturnValue('_getXMLObject', $xmldom);
        $j->buildJobObject();
        
        $this->expectError();
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