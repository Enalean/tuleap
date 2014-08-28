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

class Tracker_MasschangeDataValueExtractorTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $GLOBALS['Language'] = mock('BaseLanguage');
    }

    public function tearDown() {
        $GLOBALS['Language'] = null;

        parent::tearDown();
    }

    public function itReturnsFieldWithItNewValue() {

        stub($GLOBALS['Language'])->getText('global', 'unchanged')->returns('Unchanged');

        $masschange_data_values_manager = new Tracker_MasschangeDataValueExtractor();

        $masschange_data = array(
            1 => 'Unchanged',
            2 => 'Value01',
            3 => array('Unchanged'),
            4 => array('Value02'),
        );

        $expected_result = array(
            2 => 'Value01',
            4 => array('Value02')
        );

        $this->assertEqual(
            $expected_result,
            $masschange_data_values_manager->getNewValues($masschange_data)
        );
    }
}
