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

require_once(dirname(__FILE__).'/../include/constants.php');
require_once(dirname(__FILE__).'/../include/Tracker/Semantic/Tracker_Semantic_Status.class.php');
require_once(dirname(__FILE__).'/../include/Tracker/Tracker.class.php');
Mock::generate('Tracker');
require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_List.class.php');
Mock::generate('Tracker_FormElement_Field_List');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('Tracker_SemanticTest.php');

class Tracker_Semantic_StatusTest extends Tracker_SemanticTest {
    
    public function newTrackerSemantic($tracker, $field = null) {
        return new Tracker_Semantic_Status($tracker, $field);
    }
    
    public function newField() {
        $field = new MockTracker_FormElement_Field_List();
        $field->setReturnValue('getId', 103);
        $field->setReturnValue('getName', 'some_status');
        return $field;
    }
    
    public function setUp() {
        parent::setUp();
        $this->tracker_semantic = new Tracker_Semantic_Status($this->tracker, $this->field, array(806, 807, 808, 809));
    }
    
    public function testExport() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText','Status', array('plugin_tracker_admin_semantic','status_label'));
        $GLOBALS['Language']->setReturnValue('getText','Define the status of an artifact', array('plugin_tracker_admin_semantic','status_description'));
        
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticStatusTest.xml');
        
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker xmlns="http://codendi.org/tracker" />');
        $array_xml_mapping = array('F14' => 103,
                                   'values' => array(
                                       'F14-V66' => 806,
                                       'F14-V67' => 807,
                                       'F14-V68' => 808,
                                       'F14-V69' => 809,
                                   ));
        $this->tracker_semantic->exportToXML($root, $array_xml_mapping);
        
        $this->assertEqual((string)$xml->shortname, (string)$root->semantic->shortname);
        $this->assertEqual((string)$xml->label, (string)$root->semantic->label);
        $this->assertEqual((string)$xml->description, (string)$root->semantic->description);
        $this->assertEqual((string)$xml->field['REF'], (string)$root->semantic->field['REF']);
        $this->assertEqual(count($xml->open_values), count($root->semantic->open_values));
    }
    
     public function itReturnsTheSemanticInSOAPFormat() {
        $soap_result = $this->tracker_semantic->exportToSoap();
        $short_name = $this->tracker_semantic->getShortName();
        $field_name = $this->tracker_semantic->getField()->getName();
        $values = $this->tracker_semantic->getOpenValues();

        $this->assertEqual($soap_result, array($short_name => array('field_name' => $field_name, 'values' => $values)));
    }
    
    public function itReturnsAnEmptySOAPArray() {
        $soap_result = $this->not_defined_tracker_semantic->exportToSoap();
        $short_name = $this->not_defined_tracker_semantic->getShortName();

        $this->assertEqual($soap_result, array($short_name => array('field_name' => "", 'values' => array())));
    }
}
?>