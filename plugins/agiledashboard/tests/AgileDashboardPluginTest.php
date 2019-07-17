<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

require_once dirname(__FILE__) .'/bootstrap.php';

class AgileDashboardPluginTracker_event_semantic_from_xmlTest extends TuleapTestCase
{

    private $parameters;

    public function setUp()
    {
        parent::setUp();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<semantic type="initial_effort">
 <shortname>effort</shortname>
 <label>Effort</label>
 <description>Define the effort of an artifact</description>
 <field REF="F13"/>
</semantic>');

        $xml_mapping    = array('F13' => mock('Tracker_FormElement_Field_Float'));
        $tracker        = mock('Tracker');
        $semantic       = null;
        $type           = AgileDashBoard_Semantic_InitialEffort::NAME;

        $this->parameters = array(
            'xml'               => $xml,
            'full_semantic_xml' => $xml,
            'xml_mapping'       => $xml_mapping,
            'tracker'           => $tracker,
            'semantic'          => &$semantic,
            'type'              => $type,
        );
    }

    /**
     * Not exactly a unit test but, then again, we are testing a plugin!
     */
    public function itCreatesSemantic()
    {
        $effort_factory = AgileDashboard_Semantic_InitialEffortFactory::instance();

        $plugin = partial_mock('AgileDashboardPlugin', array('getSemanticInitialEffortFactory'));
        stub($plugin)->getSemanticInitialEffortFactory()->returns($effort_factory);

        $plugin->tracker_event_semantic_from_xml($this->parameters);

        $this->assertNotNull($this->parameters['semantic']);
        $this->assertIsA($this->parameters['semantic'], 'AgileDashBoard_Semantic_InitialEffort');
    }
}
