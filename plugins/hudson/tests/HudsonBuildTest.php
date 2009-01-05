<?php

/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonBuildTest : test the class HudsonBuild
 */

require_once(dirname(__FILE__).'/../include/HudsonBuild.class.php');
Mock::generatePartial(
    'HudsonBuild',
    'HudsonBuildTestVersion',
    array('_getXMLObject')
);

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class HudsonBuildTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function HudsonBuildTest($name = 'HudsonBuild test') {
        $this->UnitTestCase($name);
    }
    
    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    
    function testMalformedURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $this->expectError();
        $b = new HudsonBuild("toto");
    }
    function testMissingSchemeURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $this->expectError();
        $b = new HudsonBuild("code4:8080/hudson/jobs/Codendi");
    }
    function testMissingHostURL() {
        $this->expectException('HudsonJobURLMalformedException');
        $this->expectError();
        $b = new HudsonBuild("http://");
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
        
        $b = new HudsonBuildTestVersion($this);
        $b->setReturnValue('_getXMLObject', $xmldom);
        $b->buildBuildObject();
        
        $this->expectError();
    }
    
    function testSimpleJobBuild() {
        
        $build_file = dirname(__FILE__).'/resources/jobbuild.xml';
        $xmldom = simplexml_load_file($build_file);
        
        $b = new HudsonBuildTestVersion($this);
        $b->setReturnValue('_getXMLObject', $xmldom);
        
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