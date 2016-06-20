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

namespace Tuleap\Tracker\Artifact;

require_once('bootstrap.php');

use TuleapTestCase;
use Tracker_FormElement_Field_Computed;

class ChangesetValueComputedTest extends TuleapTestCase
{

    private $field;
    private $user;

    public function setUp()
    {
        parent::setUp();

        stub($GLOBALS['Language'])->getText('plugin_tracker_artifact', 'changed_from')->returns('changed from');
        stub($GLOBALS['Language'])->getText('plugin_tracker_artifact', 'to')->returns('to');
        stub($GLOBALS['Language'])->getText('plugin_tracker', 'autocompute_field')->returns('autocomputed');

        $this->field = stub('Tracker_FormElement_Field_Computed')->getName()->returns('field_computed');
        $this->user  = aUser()->withId(101)->build();
    }

    public function tearDown()
    {
        unset($GLOBALS['Language']);

        parent::tearDown();
    }

    public function testNoDiff()
    {
        $float_1 = new ChangesetValueComputed(111, $this->field, false, 456.789);
        $float_2 = new ChangesetValueComputed(111, $this->field, false, 456.789);
        $this->assertFalse($float_1->diff($float_2));
        $this->assertFalse($float_2->diff($float_1));

        $float_1 = new ChangesetValueComputed(111, $this->field, false, 0);
        $float_2 = new ChangesetValueComputed(111, $this->field, false, 0);
        $this->assertFalse($float_1->diff($float_2));
        $this->assertFalse($float_2->diff($float_1));

        $float_1 = new ChangesetValueComputed(111, $this->field, false, null);
        $float_2 = new ChangesetValueComputed(111, $this->field, false, null);
        $this->assertFalse($float_1->diff($float_2));
        $this->assertFalse($float_2->diff($float_1));
    }

    public function testDiff()
    {
        $float_1 = new ChangesetValueComputed(111, $this->field, false, 987.321);
        $float_2 = new ChangesetValueComputed(111, $this->field, false, 987);

        $this->assertEqual($float_1->diff($float_2), 'changed from 987 to 987.321');
        $this->assertEqual($float_2->diff($float_1), 'changed from 987.321 to 987');

        $float_5 = new ChangesetValueComputed(111, $this->field, false, 987.4321);
        $float_6 = new ChangesetValueComputed(111, $this->field, false, 987.4329);
        $this->assertEqual($float_5->diff($float_6), 'changed from 987.4329 to 987.4321');
        $this->assertEqual($float_6->diff($float_5), 'changed from 987.4321 to 987.4329');

        $float_7 = new ChangesetValueComputed(111, $this->field, false, 0);
        $float_8 = new ChangesetValueComputed(111, $this->field, false, 1233);
        $this->assertEqual($float_7->diff($float_8), 'changed from 1233 to 0');
        $this->assertEqual($float_8->diff($float_7), 'changed from 0 to 1233');

        $float_9  = new ChangesetValueComputed(111, $this->field, false, null);
        $float_10 = new ChangesetValueComputed(111, $this->field, false, 1233);
        $this->assertEqual($float_9->diff($float_10), 'changed from 1233 to autocomputed');
        $this->assertEqual($float_10->diff($float_9), 'changed from autocomputed to 1233');
    }
}
