<?php
/**
 * Copyright (c) Sogilis, 2016. All Rights Reserved.
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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class XMLImportFieldStrategyArtifactLinkTest extends TuleapTestCase {

    public function itShouldWorkWithCompleteMapping() {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $logger = mock('Logger');
        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink($mapping, $logger);
        $field = mock('Tracker_FormElement_Field_ArtifactLink');
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value>101</value>
                    <value>100</value>
                  </field_change>');
        $submitted_by = mock('PFUser');
        $res = $strategy->getFieldData($field, $xml_change, $submitted_by);
        $expected_res =  array("new_values" => '2,1');
        $this->assertEqual($expected_res, $res);
    }

    public function itShouldLogWhenArtifactLinkReferenceIsBroken() {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $logger = mock('Logger');
        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink($mapping, $logger);
        $field = mock('Tracker_FormElement_Field_ArtifactLink');
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value>101</value>
                  </field_change>');
        $submitted_by = mock('PFUser');
        $strategy->getFieldData($field, $xml_change, $submitted_by);
        expect($logger)->error()->count(1);
    }
}
