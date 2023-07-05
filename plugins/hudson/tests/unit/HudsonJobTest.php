<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

final class HudsonJobTest extends TestCase // @codingStandardsIgnoreLine
{
    public function testSimpleJob(): void
    {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<freeStyleProject>
 <action></action>
 <action></action>
 <action></action>
 <description></description>
 <displayName>Tuleap</displayName>
 <name>Tuleap</name>
 <url>https://example.com/hudson/job/Tuleap/</url>
 <buildable>true</buildable>
 <color>yellow</color>
 <firstBuild>
  <number>1</number>
  <url>https://example.com/hudson/job/Tuleap/1/</url>
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
  <url>https://example.com/hudson/job/Tuleap/60/</url>
 </lastBuild>
 <lastCompletedBuild>
  <number>60</number>
  <url>https://example.com/hudson/job/Tuleap/60/</url>
 </lastCompletedBuild>
 <lastFailedBuild>
  <number>30</number>
  <url>https://example.com/hudson/job/Tuleap/30/</url>
 </lastFailedBuild>
 <lastSuccessfulBuild>
  <number>60</number>
  <url>https://example.com/hudson/job/Tuleap/60/</url>
 </lastSuccessfulBuild>
 <nextBuildNumber>61</nextBuildNumber>
</freeStyleProject>
XML;

        $xml_element = simplexml_load_string($xmlstr);
        $job         = new HudsonJob('', $xml_element);

        self::assertEquals('Tuleap', $job->getName());
        self::assertEquals('https://example.com/hudson/job/Tuleap/', $job->getUrl());
        self::assertEquals(hudsonPlugin::ICONS_PATH . 'status_yellow.png', $job->getStatusIcon());
        self::assertEquals(60, $job->getLastBuildNumber());
        self::assertEquals(60, $job->getLastSuccessfulBuildNumber());
        self::assertEquals(30, $job->getLastFailedBuildNumber());
        self::assertTrue($job->hasBuilds());
        self::assertEquals(hudsonPlugin::ICONS_PATH . 'health_80_plus.gif', $job->getWeatherReportIcon());
    }

    public function testJobFromAnotherJob(): void
    {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<freeStyleProject>
 <action></action>
 <action></action>
 <description></description>
 <displayName>TestProjectExistingJob</displayName>
 <name>TestProjectExistingJob</name>
 <url>https://example.com/hudson/job/TestProjectExistingJob/</url>
 <buildable>true</buildable>
 <color>red</color>
 <firstBuild>
  <number>1</number>
  <url>https://example.com/hudson/job/TestProjectExistingJob/1/</url>
 </firstBuild>
 <healthReport>
  <description>Build stability: Tous les builds récents ont échoué.</description>
  <score>0</score>
 </healthReport>
 <inQueue>false</inQueue>
 <keepDependencies>false</keepDependencies>
 <lastBuild>
  <number>1</number>
  <url>https://example.com/hudson/job/TestProjectExistingJob/1/</url>
 </lastBuild>
 <lastCompletedBuild>
  <number>1</number>
  <url>https://example.com/hudson/job/TestProjectExistingJob/1/</url>
 </lastCompletedBuild>
 <lastFailedBuild>
  <number>1</number>
  <url>https://example.com/hudson/job/TestProjectExistingJob/1/</url>
 </lastFailedBuild>
 <nextBuildNumber>2</nextBuildNumber>
</freeStyleProject>
XML;

        $xml_element = simplexml_load_string($xmlstr);
        $job         = new HudsonJob('', $xml_element);

        self::assertEquals('TestProjectExistingJob', $job->getName());
        self::assertEquals('https://example.com/hudson/job/TestProjectExistingJob/', $job->getUrl());
        self::assertEquals(hudsonPlugin::ICONS_PATH . 'status_red.png', $job->getStatusIcon());
        self::assertEquals(1, $job->getLastBuildNumber());
        self::assertEquals(0, $job->getLastSuccessfulBuildNumber());
        self::assertEquals(1, $job->getLastFailedBuildNumber());
        self::assertTrue($job->hasBuilds());
        self::assertEquals(hudsonPlugin::ICONS_PATH . 'health_00_to_19.gif', $job->getWeatherReportIcon());
    }

    public function testJobFromExternalJob(): void
    {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<externalJob>
 <displayName>TestProjectExternalJob</displayName>
 <name>TestProjectExternalJob</name>
 <url>https://example.com/hudson/job/TestProjectExternalJob/</url>
 <buildable>false</buildable>
 <color>grey</color>
 <inQueue>false</inQueue>
 <keepDependencies>false</keepDependencies>
 <nextBuildNumber>1</nextBuildNumber>
</externalJob>
XML;

        $xml_element = simplexml_load_string($xmlstr);
        $job         = new HudsonJob('', $xml_element);

        self::assertEquals('TestProjectExternalJob', $job->getName());
        self::assertEquals('https://example.com/hudson/job/TestProjectExternalJob/', $job->getUrl());
        self::assertEquals(hudsonPlugin::ICONS_PATH . 'status_grey.png', $job->getStatusIcon());
        self::assertEquals(0, $job->getLastBuildNumber());
        self::assertEquals(0, $job->getLastSuccessfulBuildNumber());
        self::assertEquals(0, $job->getLastFailedBuildNumber());
        self::assertFalse($job->hasBuilds());
    }

    public function testJobFromMaven2Job(): void
    {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<mavenModuleSet>
 <displayName>TestProjectMaven2</displayName>
 <name>TestProjectMaven2</name>
 <url>https://example.com/hudson/job/TestProjectMaven2/</url>
 <buildable>true</buildable>
 <color>grey</color>
 <inQueue>false</inQueue>
 <keepDependencies>false</keepDependencies>
 <nextBuildNumber>1</nextBuildNumber>
</mavenModuleSet>
XML;

        $xml_element = simplexml_load_string($xmlstr);
        $job         = new HudsonJob('', $xml_element);

        self::assertEquals('TestProjectMaven2', $job->getName());
        self::assertEquals('https://example.com/hudson/job/TestProjectMaven2/', $job->getUrl());
        self::assertEquals(hudsonPlugin::ICONS_PATH . 'status_grey.png', $job->getStatusIcon());
        self::assertEquals(0, $job->getLastBuildNumber());
        self::assertEquals(0, $job->getLastSuccessfulBuildNumber());
        self::assertEquals(0, $job->getLastFailedBuildNumber());
        self::assertFalse($job->hasBuilds());
    }

    public function testJobFromMultiConfiguration(): void
    {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<matrixProject>
 <displayName>TestProjectMultiConfiguration</displayName>
 <name>TestProjectMultiConfiguration</name>
 <url>https://example.com/hudson/job/TestProjectMultiConfiguration/</url>
 <buildable>true</buildable>
 <color>grey</color>
 <inQueue>false</inQueue>
 <keepDependencies>false</keepDependencies>
 <nextBuildNumber>1</nextBuildNumber>
</matrixProject>
XML;

        $xml_element = simplexml_load_string($xmlstr);
        $job         = new HudsonJob('', $xml_element);

        self::assertEquals('TestProjectMultiConfiguration', $job->getName());
        self::assertEquals('https://example.com/hudson/job/TestProjectMultiConfiguration/', $job->getUrl());
        self::assertEquals(hudsonPlugin::ICONS_PATH . 'status_grey.png', $job->getStatusIcon());
        self::assertEquals(0, $job->getLastBuildNumber());
        self::assertEquals(0, $job->getLastSuccessfulBuildNumber());
        self::assertEquals(0, $job->getLastFailedBuildNumber());
        self::assertFalse($job->hasBuilds());
    }

    public function testNameIsReusedFromCacheWhenAvailable(): void
    {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<externalJob>
 <displayName>TestCacheName</displayName>
 <name>TestCacheName</name>
</externalJob>
XML;

        $xml_element = simplexml_load_string($xmlstr);
        $job         = new HudsonJob('NameWasCached', $xml_element);

        self::assertEquals('NameWasCached', $job->getName());
    }
}
