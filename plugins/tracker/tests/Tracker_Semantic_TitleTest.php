<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

require_once 'bootstrap.php';

class Tracker_Semantic_TitleTest extends TuleapTestCase
{

    private $xml_security;

    public function setUp()
    {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();

        $this->xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticTitleTest.xml');

        $this->tracker = mock('Tracker');
        $this->field = stub('Tracker_FormElement_Field_Text')->getId()->returns(102);
        $this->semantic_title = new Tracker_Semantic_Title($this->tracker, $this->field);
        $this->root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
    }

    public function tearDown()
    {
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function testExport()
    {
        $GLOBALS['Language'] = mock('BaseLanguage');
        $GLOBALS['Language']->setReturnValue('getText', 'Title', array('plugin_tracker_admin_semantic','title_label'));
        $GLOBALS['Language']->setReturnValue('getText', 'Define the title of an artifact', array('plugin_tracker_admin_semantic','title_description'));

        $array_mapping = array('F13' => '102');
        $this->semantic_title->exportToXML($this->root, $array_mapping);

        $this->assertEqual((string)$this->xml->shortname, (string)$this->root->semantic->shortname);
        $this->assertEqual((string)$this->xml->label, (string)$this->root->semantic->label);
        $this->assertEqual((string)$this->xml->description, (string)$this->root->semantic->description);
        $this->assertEqual((string)$this->xml->field['REF'], (string)$this->root->semantic->field['REF']);
    }

    public function itDoesntExportTheFieldIfNotDefinedInMapping()
    {
        $this->semantic_title->exportToXML($this->root, array());

        $this->assertCount($this->root->children(), 0);
    }
}
