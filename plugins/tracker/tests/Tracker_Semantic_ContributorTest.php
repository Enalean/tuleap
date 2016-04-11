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
require_once('common/language/BaseLanguage.class.php');

class Tracker_Semantic_ContributorTest extends TuleapTestCase {
    private $xml_security;

    public function setUp() {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();

        $this->tracker = mock('Tracker');
        $this->field   = stub('Tracker_FormElement_Field_List')->getId()->returns(102);

        $this->semantic = new Tracker_Semantic_Contributor($this->tracker, $this->field);

        $GLOBALS['Language'] = mock('BaseLanguage');
        $GLOBALS['Language']->setReturnValue('getText','Assigned to',array('plugin_tracker_admin_semantic','contributor_label'));
        $GLOBALS['Language']->setReturnValue('getText','Define the contributor of the artifact',array('plugin_tracker_admin_semantic','contributor_description'));
    }

    public function tearDown() {
        $this->xml_security->disableExternalLoadOfEntities();

        unset($GLOBALS['Language']);

        parent::tearDown();
    }

    public function testExport() {
        $xml           = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticContributorTest.xml');
        $root          = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_mapping = array('F13' => '102');

        $this->semantic->exportToXML($root, $array_mapping);
        
        $this->assertEqual((string)$xml->shortname, (string)$root->semantic->shortname);
        $this->assertEqual((string)$xml->label, (string)$root->semantic->label);
        $this->assertEqual((string)$xml->description, (string)$root->semantic->description);
        $this->assertEqual((string)$xml->field['REF'], (string)$root->semantic->field['REF']);
    }

    public function itDoesNotExportIfFieldIsNotExported() {
        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = array();

        $this->semantic->exportToXML($root, $array_xml_mapping);

        $this->assertEqual($root->count(), 0);
    }
}
