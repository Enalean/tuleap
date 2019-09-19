<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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
    'Tracker_SemanticFactory',
    'Tracker_SemanticFactoryTestVersion',
    array(
                          'getSemanticTitleFactory',
                          'getSemanticStatusFactory',
                          'getSemanticContributorFactory',
                          'getSemanticTooltipFactory',
    )
);

Mock::generate('Tracker');

Mock::generate('Tracker_Semantic_Status');

Mock::generate('Tracker_Semantic_Title');

Mock::generate('Tracker_Tooltip');

Mock::generate('Tracker_Semantic_Contributor');

class Tracker_SemanticFactoryTest extends TuleapTestCase
{

    /**
     * @var XML_Security
     */
    private $xml_security;

    public function setUp()
    {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
    }

    public function tearDown()
    {
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function testGetInstanceFromXml()
    {
        $xml_title       = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticTitleTest.xml');
        $xml_status      = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticStatusTest.xml');
        $xml_tooltip     = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticTooltipTest.xml');
        $xml_contributor = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticContributorTest.xml');
        $semantic_status  = new MockTracker_Semantic_Status();
        $semantic_title   = new MockTracker_Semantic_Title();
        $semantic_contributor = new MockTracker_Semantic_Contributor();
        $semantic_tooltip = new MockTracker_Tooltip();
        $semantic_status_factory  = \Mockery::mock(Tracker_Semantic_StatusFactory::class);
        $semantic_status_factory->shouldReceive('getInstanceFromXML')->andReturn($semantic_status);
        $semantic_title_factory   = \Mockery::mock(Tracker_Semantic_TitleFactory::class);
        $semantic_title_factory->shouldReceive('getInstanceFromXML')->andReturn($semantic_title);
        $semantic_tooltip_factory = \Mockery::mock(Tracker_TooltipFactory::class);
        $semantic_tooltip_factory->shouldReceive('getInstanceFromXML')->andReturn($semantic_tooltip);
        $semantic_contributor_factory = \Mockery::mock(Tracker_Semantic_ContributorFactory::class);
        $semantic_contributor_factory->shouldReceive('getInstanceFromXML')->andReturn($semantic_contributor);

        $tsf = new Tracker_SemanticFactoryTestVersion();
        $tsf->setReturnReference('getSemanticStatusFactory', $semantic_status_factory);
        $tsf->setReturnReference('getSemanticTitleFactory', $semantic_title_factory);
        $tsf->setReturnReference('getSemanticTooltipFactory', $semantic_tooltip_factory);
        $tsf->setReturnReference('getSemanticContributorFactory', $semantic_contributor_factory);

        $tracker = new MockTracker();

        $mapping = array(
            'F8'  => 108,
            'F9'  => 109,
            'F16' => 116,
            'F14' => 114
        );

        //Title
        $title = $tsf->getInstanceFromXML($xml_title, $xml_title, $mapping, $tracker);
        $this->assertReference($title, $semantic_title);

        //Status
        $status = $tsf->getInstanceFromXML($xml_status, $xml_status, $mapping, $tracker);
        $this->assertReference($status, $semantic_status);

        //Tooltip
        $tooltip = $tsf->getInstanceFromXML($xml_tooltip, $xml_tooltip, $mapping, $tracker);
        $this->assertReference($tooltip, $semantic_tooltip);

        //Contributor
        $contributor = $tsf->getInstanceFromXML($xml_contributor, $xml_contributor, $mapping, $tracker);
        $this->assertReference($contributor, $semantic_contributor);
    }
}
