<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
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

class Tracker_FormElement_DateTimeFormatterTest extends TuleapTestCase {

    /** @var Tracker_FormElement_DateTimeFormatter */
    private $date_formatter;

    public function setUp() {
        parent::setUp();

        $this->field          = aMockDateWithoutTimeField()->withId(07)->build();
        $this->date_formatter = new Tracker_FormElement_DateTimeFormatter($this->field);

        $user                 = stub('PFUser')->getPreference('user_csv_dateformat')->returns('');
        $user_manager         = stub('UserManager')->getCurrentUser()->returns($user);

        UserManager::setInstance($user_manager);
    }

    public function tearDown() {
        parent::tearDown();

        UserManager::clearInstance();
    }

    public function itFormatsTimestampInRightFormat() {
        $timestamp = 1409752174;
        $expected  = '2014-09-03 15:49';

        $this->assertEqual($expected, $this->date_formatter->formatDate($timestamp));
    }

    public function itFormatsTimestampInRightFormatForHoursBeforeNoon() {
        $timestamp = 1409708974;
        $expected  = '2014-09-03 03:49';

        $this->assertEqual($expected, $this->date_formatter->formatDate($timestamp));
    }

    public function itValidatesWellFormedValue() {
        $value    = '2014-09-03 03:49';

        $this->assertTrue($this->date_formatter->validate($value));
    }

    public function itDoesNotValidateNotWellFormedDate() {
        $value    = '2014/09/03 03:49';

        $this->assertFalse($this->date_formatter->validate($value));
    }

    public function itDoesNotValidateNotWellFormedTime() {
        $value    = '2014-09-03 03-49-34';

        $this->assertFalse($this->date_formatter->validate($value));
    }

    public function itDoesNotValidateDateIfNoSpaceBetweenDateAndTime() {
        $value    = '2014-09-0303:49';

        $this->assertFalse($this->date_formatter->validate($value));
    }

    public function itDoesNotValidateDateIfNoTime() {
        $value    = '2014-09-03';

        $this->assertFalse($this->date_formatter->validate($value));
    }

    public function itReturnsWellFormedDateForCSVWihoutSecondsEvenIfGiven() {
        $date_exploded = array(
            '2014',
            '09',
            '03',
            '08',
            '06',
            '12'
        );

        $expected = '2014-09-03 08:06';

        $this->assertEqual($expected, $this->date_formatter->getFieldDataForCSVPreview($date_exploded));
    }

    public function itReturnsWellFormedDateForCSV() {
        $date_exploded = array(
            '2014',
            '09',
            '03',
            '08',
            '06'
        );

        $expected = '2014-09-03 08:06';

        $this->assertEqual($expected, $this->date_formatter->getFieldDataForCSVPreview($date_exploded));
    }

}
