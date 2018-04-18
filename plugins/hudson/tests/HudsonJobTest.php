<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
    'HudsonJob',
    'HudsonJobTestVersion',
    array('_getXMLObject')
);

class HudsonJobTest extends TuleapTestCase {

    private $http_client;

    public function setUp()
    {
        parent::setUp();
        $this->http_client = mock('Http_Client');
    }

    public function testMalformedURL()
    {
        $this->expectException('HudsonJobURLMalformedException');
        new HudsonJob("toto", $this->http_client);
    }

    public function testMissingSchemeURL()
    {
        $this->expectException('HudsonJobURLMalformedException');
        new HudsonJob("code4:8080/hudson/jobs/Codendi", $this->http_client);
    }

    public function testMissingHostURL()
    {
        $this->expectException('HudsonJobURLMalformedException');
        new HudsonJob("http://", $this->http_client);
    }

    public function testURLWithBuildWithParams()
    {
        $job = new HudsonJob('http://shunt.cro.enalean.com:8080/job/build_params/buildWithParameters?Stuff=truc', $this->http_client);
        $this->assertEqual($job->getJobUrl(), 'http://shunt.cro.enalean.com:8080/job/build_params/api/xml');
    }

    public function testSimpleJob()
    {
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

        $j = new HudsonJobTestVersion($this);
        $j->setReturnValue('_getXMLObject', $xmldom);

        $j->__construct("http://myCIserver/jobs/myCIjob", $this->http_client);
        
        $this->assertEqual($j->getName(), "Codendi");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/");
        $this->assertEqual($j->getStatusIcon(), hudsonPlugin::ICONS_PATH."status_yellow.png");
        
        $this->assertEqual($j->getLastBuildNumber(), "60");
        $this->assertEqual($j->getLastSuccessfulBuildNumber(), "60");
        $this->assertEqual($j->getLastFailedBuildNumber(), "30");
        $this->assertTrue($j->hasBuilds());

        $this->assertEqual($j->getWeatherReportIcon(), hudsonPlugin::ICONS_PATH."health_80_plus.gif");
        
    }
    
    public function testJobFromAnotherJob()
    {
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

        $j->__construct("http://myCIserver/jobs/myCIjob", $this->http_client);
        
        $this->assertEqual($j->getName(), "TestProjectExistingJob");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExistingJob/");
        $this->assertEqual($j->getStatusIcon(), hudsonPlugin::ICONS_PATH."status_red.png");
        
        $this->assertEqual($j->getLastBuildNumber(), "1");
        $this->assertNull($j->getLastSuccessfulBuildNumber());
        $this->assertEqual($j->getLastFailedBuildNumber(), "1");
        $this->assertTrue($j->hasBuilds());

        $this->assertEqual($j->getWeatherReportIcon(), hudsonPlugin::ICONS_PATH."health_00_to_19.gif");
        
    }
    
    public function testJobFromExternalJob()
    {
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

        $j->__construct("http://myCIserver/jobs/myCIjob", $this->http_client);
        
        $this->assertEqual($j->getName(), "TestProjectExternalJob");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectExternalJob/");
        $this->assertEqual($j->getStatusIcon(), hudsonPlugin::ICONS_PATH."status_grey.png");
        
        $this->assertNull($j->getLastBuildNumber());
        $this->assertNull($j->getLastSuccessfulBuildNumber());
        $this->assertNull($j->getLastFailedBuildNumber());
        $this->assertFalse($j->hasBuilds());

    }
    
    public function testJobFromMaven2Job()
    {
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

        $j->__construct("http://myCIserver/jobs/myCIjob", $this->http_client);
        
        $this->assertEqual($j->getName(), "TestProjectMaven2");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectMaven2/");
        $this->assertEqual($j->getStatusIcon(), hudsonPlugin::ICONS_PATH."status_grey.png");
        
        $this->assertNull($j->getLastBuildNumber());
        $this->assertNull($j->getLastSuccessfulBuildNumber());
        $this->assertNull($j->getLastFailedBuildNumber());
        $this->assertFalse($j->hasBuilds());

    }
    
    public function testJobFromMultiConfiguration()
    {
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

        $j->__construct("http://myCIserver/jobs/myCIjob", $this->http_client);
        
        $this->assertEqual($j->getName(), "TestProjectMultiConfiguration");
        $this->assertEqual($j->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/TestProjectMultiConfiguration/");
        $this->assertEqual($j->getStatusIcon(), hudsonPlugin::ICONS_PATH."status_grey.png");
        
        $this->assertNull($j->getLastBuildNumber());
        $this->assertNull($j->getLastSuccessfulBuildNumber());
        $this->assertNull($j->getLastFailedBuildNumber());
        $this->assertFalse($j->hasBuilds());
    }
}
