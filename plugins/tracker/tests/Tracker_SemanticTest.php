<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


abstract class Tracker_SemanticTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->tracker = new MockTracker();
        $this->field = $this->newField();
        $this->tracker_semantic = $this->newTrackerSemantic($this->tracker, $this->field);
        $this->not_defined_tracker_semantic = $this->newTrackerSemantic($this->tracker);
    }

    public function itReturnsTheSemanticInSOAPFormat() {
        $soap_result = $this->tracker_semantic->exportToSoap();
        $short_name = $this->tracker_semantic->getShortName();
        $field_name = $this->tracker_semantic->getField()->getName();

        $this->assertEqual($soap_result, array($short_name => array('field_name' => $field_name)));
    }

    public function itReturnsAnEmptySOAPArray() {
        $soap_result = $this->not_defined_tracker_semantic->exportToSoap();
        $short_name = $this->not_defined_tracker_semantic->getShortName();

        $this->assertEqual($soap_result, array($short_name => array('field_name' => "")));
    }
    public abstract function newField();

    public abstract function newTrackerSemantic($tracker, $field=null);
}

?>
