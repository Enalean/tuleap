<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker;

use TuleapTestCase;
use Tracker_Semantic_Description;
use SimpleXMLElement;

require_once('bootstrap.php');

class SemanticDescriptionTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $tracker = mock('Tracker');
        $field   = stub('Tracker_FormElement_Field_Text')->getId()->returns(104);

        $this->semantic = new Tracker_Semantic_Description($tracker, $field);
    }

    public function testExport()
    {
        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = array('F14' => 104);

        $this->semantic->exportToXML($root, $array_xml_mapping);

        $this->assertTrue($root->count() > 0);
        $this->assertEqual($root->semantic['type'], 'description');
        $this->assertEqual($root->semantic->shortname, 'description');
    }

    public function itDoesNotExportIfFieldIsNotExported()
    {
        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = array();

        $this->semantic->exportToXML($root, $array_xml_mapping);

        $this->assertEqual($root->count(), 0);
    }
}
