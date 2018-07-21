<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) Jtekt, 2014. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2014. Jtekt Europe SAS.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class TestableFlowChartDataBuilder extends GraphOnTrackersV5_CumulativeFlow_DataBuilder {
    public function filterEmptyLines(array $array) {
        return parent::filterEmptyLines($array);
    }
}

class CumulativeFlowChartTest extends TestCase
{
    public function testFilterEmptyLines() {
        $initial_array= array(
            "1393891200" => array
                (
                    "8" => 0           , "7" => 0,
                    "6" => 0           , "5 - Major" => 0,
                    "9 - Critical" => 0, "4" => 0,
                    "3" => 0           , "2" => 0,
                    "1 - Ordinary" => 0, "None" => 0
                ),
            "1393977600" => array
                (
                    "8" => 0           , "7" => 0,
                    "6" => 0           , "5 - Major" => 0,
                    "9 - Critical" => 0, "4" => 0,
                    "3" => 0           , "2" => 0,
                    "1 - Ordinary" => 0, "None" => 0
                ),
            "1394064000" => array
                (
                    "8" => 0           , "7" => 0,
                    "6" => 0           , "5 - Major" => 0,
                    "9 - Critical" => 0, "4" => 3,
                    "3" => 1           , "2" => 1,
                    "1 - Ordinary" => 0, "None" => 0
                ),
            "1394150400" => array
                (
                    "8" => 0           , "7" => 0,
                    "6" => 0           , "5 - Major" => 0,
                    "9 - Critical" => 0, "4" => 3,
                    "3" => 1           , "2" => 1,
                    "1 - Ordinary" => 0, "None" => 0
                ),
            "1394236800" => array
                (
                    "8" => 0           , "7" => 0,
                    "6" => 0           , "5 - Major" => 0,
                    "9 - Critical" => 0, "4" => 3,
                    "3" => 1           , "2" => 1,
                    "1 - Ordinary" => 0, "None" => 1
                )
        );
        $expected_array = array(
            "1393891200" => array
                (
                    "4" => 0           , "3" => 0,
                    "2" => 0           , "None" => 0
                ),
            "1393977600" => array
                (
                    "4" => 0           , "3" => 0,
                    "2" => 0           , "None" => 0
                ),
            "1394064000" => array
                (
                    "4" => 3           , "3" => 1,
                    "2" => 1           , "None" => 0
                ),
            "1394150400" => array
                (
                    "4" => 3           , "3" => 1,
                    "2" => 1           , "None" => 0
                ),
            "1394236800" => array
                (
                    "4" => 3          , "3" => 1,
                    "2" => 1          , "None" =>1
                )
        );

        $data_builder = new TestableFlowChartDataBuilder(null, null);
        $this->assertEquals($data_builder->filterEmptyLines($initial_array), $expected_array);
    }
}
