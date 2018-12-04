<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_Artifact_ChangesetValue_IntegerTest extends TuleapTestCase {

    private $field;
    private $user;

    public function setUp() {
        parent::setUp();

        $base_language = mock('BaseLanguage');
        stub($base_language)->getText('plugin_tracker_artifact','changed_from')->returns('changed from');
        stub($base_language)->getText('plugin_tracker_artifact','to')->returns('to');

        $GLOBALS['Language'] = $base_language;

        $this->field = stub('Tracker_FormElement_Field_Integer')->getName()->returns('field_int');
        $this->user  = aUser()->withId(101)->build();

        $this->changeset = mock('Tracker_Artifact_Changeset');
    }

    public function tearDown() {
        unset($GLOBALS['Language']);

        parent::tearDown();
    }

    public function testIntegers() {

        $integer = new Tracker_Artifact_ChangesetValue_Integer(111, $this->changeset, $this->field, false, 42);

        $this->assertEqual($integer->getInteger(), 42);
        $this->assertNotIdentical($integer->getInteger(), '42');
        $this->assertIdentical($integer->getValue(), 42);

        $string_int = new Tracker_Artifact_ChangesetValue_Integer(111, $this->changeset, $this->field, false, '55');
        $this->assertEqual($string_int->getInteger(), 55);
        $this->assertEqual($string_int->getInteger(), '55');
        $this->assertNotIdentical($string_int->getInteger(), '55');
        $this->assertIdentical($string_int->getValue(), 55);

        $null_int = new Tracker_Artifact_ChangesetValue_Integer(111, $this->changeset, $this->field, false, null);
        $this->assertNull($null_int->getInteger());
        $this->assertNull($null_int->getValue());
    }

    public function testNoDiff() {
        $this->field = mock('Tracker_FormElement_Field_Integer');
        $int_1 = new Tracker_Artifact_ChangesetValue_Integer(111, $this->changeset, $this->field, false, 54);
        $int_2 = new Tracker_Artifact_ChangesetValue_Integer(111, $this->changeset, $this->field, false, 54);
        $this->assertFalse($int_1->diff($int_2));
        $this->assertFalse($int_2->diff($int_1));
    }

    public function testDiff() {
        $this->field = mock('Tracker_FormElement_Field_Integer');
        $int_1 = new Tracker_Artifact_ChangesetValue_Integer(111, $this->changeset, $this->field, false, 66);
        $int_2 = new Tracker_Artifact_ChangesetValue_Integer(111, $this->changeset, $this->field, false, 666);
        $this->assertEqual($int_1->diff($int_2), 'changed from 666 to 66');
    }
}

class Tracker_Artifact_ChangesetValue_Integer_RESTTest extends TuleapTestCase {

    public function itReturnsTheRESTValue() {
        $field = stub('Tracker_FormElement_Field_Integer')->getName()->returns('field_int');
        $user  = aUser()->withId(101)->build();

        $changeset = new Tracker_Artifact_ChangesetValue_Integer(111, mock('Tracker_Artifact_Changeset'), $field, true, 556);
        $representation = $changeset->getRESTValue($user, $changeset);

        $this->assertEqual($representation->value, 556);
    }
}
