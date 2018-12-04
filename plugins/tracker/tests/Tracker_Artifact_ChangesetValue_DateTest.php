<?php
/**
 * Copyright (c) Enalean, 2015-2017. All Rights Reserved.
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
require_once('bootstrap.php');

class Tracker_Artifact_ChangesetValue_DateTest extends TuleapTestCase {

    private $field;
    private $user;
    private $changeset;

    public function setUp() {
        parent::setUp();

        $this->field = stub('Tracker_FormElement_Field_Date')->getName()->returns('field_date');
        $this->user  = aUser()->withId(101)->build();

        $this->changeset = mock('Tracker_Artifact_Changeset');
    }

    public function tearDown() {
        unset($GLOBALS['Language']);

        parent::tearDown();
    }

    public function testDates() {
        stub($this->field)->formatDateForDisplay(1221221466)->returns("12/09/2008");
        $date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $this->assertEqual($date->getTimestamp(), 1221221466);
        $this->assertEqual($date->getDate(), "12/09/2008");

        stub($this->field)->formatDateForDisplay(1221221467)->returns("2008-09-12");
        $date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221467);
        $this->assertEqual($date->getTimestamp(), 1221221467);
        $this->assertEqual($date->getDate(), "2008-09-12");

        $this->assertEqual($date->getValue(), "2008-09-12");

        $null_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        $this->assertNull($null_date->getTimestamp());
        $this->assertEqual($null_date->getDate(), '');
    }
}

class Tracker_Artifact_ChangesetValue_DateDiffTest extends TuleapTestCase {


    private $field;
    private $base_language;
    private $changeset;
    private $tz;

    public function setUp() {
        parent::setUp();

        $this->base_language = mock('BaseLanguage');
        stub($this->base_language)->getText('plugin_tracker_artifact','changed_from')->returns('changed from');
        stub($this->base_language)->getText('plugin_tracker_artifact','to')->returns('to');
        stub($this->base_language)->getText('plugin_tracker_artifact','cleared')->returns('cleared');
        stub($this->base_language)->getText('plugin_tracker_artifact','set_to')->returns('set_to');

        stub($this->base_language)->getText('system', 'datefmt_short')->returns(Tracker_FormElement_DateFormatter::DATE_FORMAT);

        $GLOBALS['Language'] = $this->base_language;

        $this->field = stub('Tracker_FormElement_Field_Date')->getName()->returns('field_date');
        stub($this->field)->formatDateForDisplay(1221221466)->returns("2008-09-12");
        stub($this->field)->formatDateForDisplay(1234567890)->returns("2009-02-14");

        $this->changeset = mock('Tracker_Artifact_Changeset');

        $this->tz = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');
    }

    public function tearDown() {
        date_default_timezone_set($this->tz);
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testNoDiff() {
        $date_1 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $date_2 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $this->assertFalse($date_1->diff($date_2));
        $this->assertFalse($date_2->diff($date_1));
    }

    public function testDiffBetween2dates() {
        $date_1 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $date_2 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $this->assertEqual($date_1->diff($date_2), 'changed from 2009-02-14 to 2008-09-12');
        $this->assertEqual($date_2->diff($date_1), 'changed from 2008-09-12 to 2009-02-14');
    }

    public function testDiffDateSet() {
        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 0);
        $new_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $this->assertEqual($new_date->diff($previous_date), 'set_to 2009-02-14');
    }

    public function testDiffDateCleared() {
        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $new_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        $this->assertEqual($new_date->diff($previous_date), 'cleared');
    }

    public function testDiffDateDidNotChanged() {
        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $new_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $this->assertEqual($new_date->diff($previous_date), '');
    }

    public function testDiffNoValueSubmittedYetBothDatesAreNull() {
        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        $new_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        $this->assertEqual($new_date->diff($previous_date), '');
    }
}
