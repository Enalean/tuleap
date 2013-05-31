<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/Statistics_Services_UsageFormatter.class.php';
require_once 'www/include/user.php';

class Statistics_Services_UsageFormatterTest extends TuleapTestCase {

    public function itBuildsData() {
        $stats_formatter = mock('Statistics_Formatter');
        $formatter  = new Statistics_Services_UsageFormatter($stats_formatter);

        $input_datas = array(
            array(
                'group_id' => 1,
                'result'   => 'res1'
            ),
            array(
                'group_id' => 87,
                'result'   => 'res2'
            ),
            array(
                'group_id' => 104,
                'result'   => 'res3'
            )
        );

        $expected = array(
            1 => array(
                "title" => 'res1'
            ),
            87 => array(
                "title" => 'res2'
            ),
            104 => array(
                "title" => 'res3'
            )
        );

        $datas = $formatter->buildDatas($input_datas, "title");
        $this->assertEqual($datas, $expected);
    }

    public function _itOnlyAddTitlesWhithEmptyData() {

    }

}
?>
