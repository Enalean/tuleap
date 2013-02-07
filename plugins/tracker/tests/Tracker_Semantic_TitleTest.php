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
require_once('bootstrap.php');
Mock::generate('Tracker');
Mock::generate('Tracker_FormElement_Field_Text');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class Tracker_Semantic_TitleTest extends UnitTestCase {

    public function testExport() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText','Title',array('plugin_tracker_admin_semantic','title_label'));
        $GLOBALS['Language']->setReturnValue('getText','Define the title of an artifact',array('plugin_tracker_admin_semantic','title_description'));

        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticTitleTest.xml');

        $tracker = new MockTracker();
        $f = new MockTracker_FormElement_Field_Text();
        $f->setReturnValue('getId', 102);
        $tst = new Tracker_Semantic_Title($tracker, $f);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker xmlns="http://codendi.org/tracker" />');
        $array_mapping = array('F13' => '102');
        $tst->exportToXML($root, $array_mapping);

        $this->assertEqual((string)$xml->shortname, (string)$root->semantic->shortname);
        $this->assertEqual((string)$xml->label, (string)$root->semantic->label);
        $this->assertEqual((string)$xml->description, (string)$root->semantic->description);
        $this->assertEqual((string)$xml->field['REF'], (string)$root->semantic->field['REF']);
    }

}

?>