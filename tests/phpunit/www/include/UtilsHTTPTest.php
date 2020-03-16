<?php
/**
 * Copyright (c) Enalean, 2011 - 2019. All Rights Reserved.
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

namespace Tuleap;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../src/www/include/utils.php';

class UtilsHTTPTest extends TestCase
{

    public function testItExtractBody()
    {
        $string = "Content-type: sdfsdf\r\n\r\nThe body";
        list($headers, $body) = http_split_header_body($string);

        $this->assertSame("The body", $body);
    }

    public function testItExtractBodyThatStartsWithNul()
    {
        $string = "Content-type: sdfsdf\r\n\r\n" . (0x00) . "The body";
        list($headers, $body) = http_split_header_body($string);

        $this->assertSame((0x00) . "The body", $body);
    }

    public function testItExtractBodyThatStartsWithLN()
    {
        list($headers, $body) = http_split_header_body("Content-type: sdfsdf\r\n\r\n
The body");
        $this->assertSame("\nThe body", $body);
    }

    public function testItExtractHeaders()
    {
        list($headers, $body) = http_split_header_body("Content-disposition: anefe
Content-type: sdfsdf\r\n\r\nThe body");
        $this->assertSame("Content-disposition: anefe\nContent-type: sdfsdf", $headers);
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=5604&group_id=101 ViewVC download broken when file start with 0x00
     */
    public function testItExtractsBodyWithBinaryData()
    {
        list($headers, $body) = http_split_header_body(file_get_contents(dirname(__FILE__) . '/_fixtures/svn_bin_data'));
        $this->assertSame("Content-Type: text/plain", $headers);
    }
}
