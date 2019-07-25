<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
require_once __DIR__ . '/../../../src/www/include/utils.php';

class Tracker_FormElement_DateFormatterTest extends TuleapTestCase {

    /** @var Tracker_FormElement_DateFormatter */
    private $date_formatter;

    public function setUp() {
        parent::setUp();

        $this->field          = aMockDateWithoutTimeField()->withId(07)->build();
        $this->date_formatter = new Tracker_FormElement_DateFormatter($this->field);
    }

    public function itFormatsTimestampInRightFormat() {
        $timestamp = 1409752174;
        $expected  = '2014-09-03';

        $this->assertEqual($expected, $this->date_formatter->formatDate($timestamp));
    }

    public function itValidatesWellFormedValue() {
        $value    = '2014-09-03';

        $this->assertTrue($this->date_formatter->validate($value));
    }

    public function itDoesNotValidateNotWellFormedValue() {
        $value    = '2014/09/03';

        $this->assertFalse($this->date_formatter->validate($value));
    }

}
