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

require_once(dirname(__FILE__).'/../include/HudsonJob.class.php');
Mock::generatePartial(
    'HudsonJob',
    'HudsonJobTestVersion',
    array('_getXMLObject', 'getIconsPath', 'getHudsonControler')
);
Mock::generatePartial(
    'HudsonJob',
    'HudsonJobTestColorVersion',
    array('getColor')
);

require_once(dirname(__FILE__).'/../include/hudson.class.php');
Mock::generate('hudson');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class HudsonJobTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function HudsonJobTest($name = 'HudsonJob test') {
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
        $j->setReturnValue('getIconsPath', '');
        $j->buildJobObject();
        
        $this->expectError();
    }
    
    function testSimpleJob() {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<freeStyleProject>
 <action></action>
 <action></action>
 <action></action>
 <description></description>
 <displayName>Codendi</displayName>
 <name>Codendi</name>
 <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/</url>
 <buildable>true</buildable>
 <color>yellow</color>
 <firstBuild>
  <number>1</number>
  <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/1/</url>
 </firstBuild>
 <healthReport>
  <description>Build stability: 1 des 5 derniers builds ont échoué.</description>
  <score>79</score>
 </healthReport>
 <healthReport>
  <description>Résultats des tests: 5 tests failing out of a total of 403 tests.</description>
  <score>98</score>
 </healthReport>
 <inQueue>false</inQueue>
 <keepDependencies>false</keepDependencies>
 <lastBuild>
  <number>60</number>
  <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/60/</url>
 </lastBuild>
 <lastCompletedBuild>
  <number>60</number>
  <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/60/</url>
 </lastCompletedBuild>
 <lastFailedBuild>
  <number>30</number>
  <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/30/</url>
 </lastFailedBuild>
 <lastSuccessfulBuild>
  <number>60</number>
  <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/60/</url>
 </lastSuccessfulBuild>
 <nextBuildNumber>61</nextBuildNumber>
</freeStyleProject>        
XML;
        
        $xmldom = new SimpleXMLElement($xmlstr);
        //var_dump($xmldom);
        
        $j = new HudsonJobTestVersion($this);
        $j->setReturnValue('_getXMLObject', $xmldom);
        $mh = new Mockhudson($this);
        $mh->setReturnValue('getIconsPath', '');
        $j->setReturnValue('getHudsonControler', $mh);
        $j->setReturnValue('getIconsPath', '');
        
        $j->HudsonJob("http://myCIserver/jobs/myCIjob");
        
        $this->assertEqual($j->getProjectStyle(), "freeStyleProject");
        $this->assertEqual($j->getName(), "Codendi");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/");
        $this->assertEqual($j->getColor(), "yellow");
        $this->assertEqual($j->getStatusIcon(), "status_yellow.png");
        
        $this->assertEqual($j->getLastBuildNumber(), "60");
        $this->assertEqual($j->getLastSuccessfulBuildNumber(), "60");
        $this->assertEqual($j->getLastFailedBuildNumber(), "30");
        $this->assertEqual($j->getNextBuildNumber(), "61");
        $this->assertTrue($j->hasBuilds());
        $this->assertTrue($j->isBuildable());
        
        $this->assertEqual($j->getHealthScores(), array('79', '98'));
        $this->assertEqual($j->getHealthAverageScore(), '88');
        $this->assertEqual($j->getWeatherReportIcon(), "health_80_plus.gif");
        
    }
    
    function testJobFromAnotherJob() {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<freeStyleProject>
 <action></action>
 <action></action>
 <description></description>
 <displayName>TestProjectExistingJob</displayName>
 <name>TestProjectExistingJob</name>
 <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExistingJob/</url>
 <buildable>true</buildable>
 <color>red</color>
 <firstBuild>
  <number>1</number>
  <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExistingJob/1/</url>
 </firstBuild>
 <healthReport>
  <description>Build stability: Tous les builds récents ont échoué.</description>
  <score>0</score>
 </healthReport>
 <inQueue>false</inQueue>
 <keepDependencies>false</keepDependencies>
 <lastBuild>
  <number>1</number>
  <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExistingJob/1/</url>
 </lastBuild>
 <lastCompletedBuild>
  <number>1</number>
  <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExistingJob/1/</url>
 </lastCompletedBuild>
 <lastFailedBuild>
  <number>1</number>
  <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExistingJob/1/</url>
 </lastFailedBuild>
 <nextBuildNumber>2</nextBuildNumber>
</freeStyleProject>
XML;

        $xmldom = new SimpleXMLElement($xmlstr);
        
        $j = new HudsonJobTestVersion($this);
        $j->setReturnValue('_getXMLObject', $xmldom);
        $mh = new Mockhudson($this);
        $mh->setReturnValue('getIconsPath', '');
        $j->setReturnValue('getHudsonControler', $mh);
        $j->setReturnValue('getIconsPath', '');
        
        $j->HudsonJob("http://myCIserver/jobs/myCIjob");
        
        $this->assertEqual($j->getProjectStyle(), "freeStyleProject");
        $this->assertEqual($j->getName(), "TestProjectExistingJob");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExistingJob/");
        $this->assertEqual($j->getColor(), "red");
        $this->assertEqual($j->getStatusIcon(), "status_red.png");
        
        $this->assertEqual($j->getLastBuildNumber(), "1");
        $this->assertNull($j->getLastSuccessfulBuildNumber());
        $this->assertEqual($j->getLastFailedBuildNumber(), "1");
        $this->assertEqual($j->getNextBuildNumber(), "2");
        $this->assertTrue($j->hasBuilds());
        $this->assertTrue($j->isBuildable());
        
        $this->assertEqual($j->getHealthScores(), array('0'));
        $this->assertEqual($j->getHealthAverageScore(), '0');
        $this->assertEqual($j->getWeatherReportIcon(), "health_00_to_19.gif");
        
    }
    
    function testJobFromExternalJob() {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<externalJob>
 <displayName>TestProjectExternalJob</displayName>
 <name>TestProjectExternalJob</name>
 <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExternalJob/</url>
 <buildable>false</buildable>
 <color>grey</color>
 <inQueue>false</inQueue>
 <keepDependencies>false</keepDependencies>
 <nextBuildNumber>1</nextBuildNumber>
</externalJob>
XML;

        $xmldom = new SimpleXMLElement($xmlstr);
        
        $j = new HudsonJobTestVersion($this);
        $j->setReturnValue('_getXMLObject', $xmldom);
        $mh = new Mockhudson($this);
        $mh->setReturnValue('getIconsPath', '');
        $j->setReturnValue('getHudsonControler', $mh);
        $j->setReturnValue('getIconsPath', '');
        
        $j->HudsonJob("http://myCIserver/jobs/myCIjob");
        
        $this->assertEqual($j->getProjectStyle(), "externalJob");
        $this->assertEqual($j->getName(), "TestProjectExternalJob");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExternalJob/");
        $this->assertEqual($j->getColor(), "grey");
        $this->assertEqual($j->getStatusIcon(), "status_grey.png");
        
        $this->assertNull($j->getLastBuildNumber());
        $this->assertNull($j->getLastSuccessfulBuildNumber());
        $this->assertNull($j->getLastFailedBuildNumber());
        $this->assertEqual($j->getNextBuildNumber(), "1");
        $this->assertFalse($j->hasBuilds());
        $this->assertFalse($j->isBuildable());
        
        $this->assertEqual($j->getHealthScores(), array());
        $this->assertEqual($j->getHealthAverageScore(), '0');
        
    }
    
    function testJobFromMaven2Job() {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<mavenModuleSet>
 <displayName>TestProjectMaven2</displayName>
 <name>TestProjectMaven2</name>
 <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectMaven2/</url>
 <buildable>true</buildable>
 <color>grey</color>
 <inQueue>false</inQueue>
 <keepDependencies>false</keepDependencies>
 <nextBuildNumber>1</nextBuildNumber>
</mavenModuleSet>
XML;

        $xmldom = new SimpleXMLElement($xmlstr);
        
        $j = new HudsonJobTestVersion($this);
        $j->setReturnValue('_getXMLObject', $xmldom);
        $mh = new Mockhudson($this);
        $mh->setReturnValue('getIconsPath', '');
        $j->setReturnValue('getHudsonControler', $mh);
        $j->setReturnValue('getIconsPath', '');
        
        $j->HudsonJob("http://myCIserver/jobs/myCIjob");
        
        $this->assertEqual($j->getProjectStyle(), "mavenModuleSet");
        $this->assertEqual($j->getName(), "TestProjectMaven2");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectMaven2/");
        $this->assertEqual($j->getColor(), "grey");
        $this->assertEqual($j->getStatusIcon(), "status_grey.png");
        
        $this->assertNull($j->getLastBuildNumber());
        $this->assertNull($j->getLastSuccessfulBuildNumber());
        $this->assertNull($j->getLastFailedBuildNumber());
        $this->assertEqual($j->getNextBuildNumber(), "1");
        $this->assertFalse($j->hasBuilds());
        $this->assertTrue($j->isBuildable());
        
        $this->assertEqual($j->getHealthScores(), array());
        $this->assertEqual($j->getHealthAverageScore(), '0');
        
    }
    
    function testJobFromMultiConfiguration() {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<matrixProject>
 <displayName>TestProjectMultiConfiguration</displayName>
 <name>TestProjectMultiConfiguration</name>
 <url>http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectMultiConfiguration/</url>
 <buildable>true</buildable>
 <color>grey</color>
 <inQueue>false</inQueue>
 <keepDependencies>false</keepDependencies>
 <nextBuildNumber>1</nextBuildNumber>
</matrixProject>
XML;

        $xmldom = new SimpleXMLElement($xmlstr);
        
        $j = new HudsonJobTestVersion($this);
        $j->setReturnValue('_getXMLObject', $xmldom);
        $mh = new Mockhudson($this);
        $mh->setReturnValue('getIconsPath', '');
        $j->setReturnValue('getHudsonControler', $mh);
        $j->setReturnValue('getIconsPath', '');
        
        $j->HudsonJob("http://myCIserver/jobs/myCIjob");
        
        $this->assertEqual($j->getProjectStyle(), "matrixProject");
        $this->assertEqual($j->getName(), "TestProjectMultiConfiguration");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectMultiConfiguration/");
        $this->assertEqual($j->getColor(), "grey");
        $this->assertEqual($j->getStatusIcon(), "status_grey.png");
        
        $this->assertNull($j->getLastBuildNumber());
        $this->assertNull($j->getLastSuccessfulBuildNumber());
        $this->assertNull($j->getLastFailedBuildNumber());
        $this->assertEqual($j->getNextBuildNumber(), "1");
        $this->assertFalse($j->hasBuilds());
        $this->assertTrue($j->isBuildable());
        
        $this->assertEqual($j->getHealthScores(), array());
        $this->assertEqual($j->getHealthAverageScore(), '0');
        
    }
    
    function testColorNoAnime1() {
        $j = new HudsonJobTestColorVersion($this);
        $j->setReturnValue('getColor', "blue");
        $this->assertEqual($j->getColorNoAnime(), "blue");
    }  
    function testColorNoAnime2() {
        $j = new HudsonJobTestColorVersion($this);
        $j->setReturnValue('getColor', "blue_anime");
        $this->assertEqual($j->getColorNoAnime(), "blue");
    }
    function testColorNoAnime3() {
        $j = new HudsonJobTestColorVersion($this);        
        $j->setReturnValue('getColor', "grey");
        $this->assertEqual($j->getColorNoAnime(), "grey");
    }
    function testColorNoAnime4() {
        $j = new HudsonJobTestColorVersion($this);  
        $j->setReturnValue('getColor', "grey_anime");
        $this->assertEqual($j->getColorNoAnime(), "grey");
    }
    
}

?>