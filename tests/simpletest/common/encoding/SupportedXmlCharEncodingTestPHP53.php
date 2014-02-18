<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'common/encoding/SupportedXmlCharEncoding.class.php';

class Encoding_SupportedXmlCharEncodingPHP53_getXMLCompatibleStringTest  extends TuleapTestCase {

    /**
     * This test can be only run in php53. That is beacuse ENT_IGNORE is not available in
     * earlier versions of php
     */
     public function itReplacesBadCharacters() {
        $bad_chars =  array(
            "\x01" => ' ',
            "\x02" => ' ',
            "\x03" => ' ',
            "\x04" => ' ',
            "\x05" => ' ',
            "\x06" => ' ',
            "\x07" => ' ',
            "\x08" => ' ',
            "\x0b" => ' ',
            "\x0c" => ' ',
            "\x0e" => ' ',
            "\x0f" => ' ',
            "\x11" => ' ',
            "’"    => '&rsquo;',
        );

        foreach ($bad_chars as $bad_char => $replace) {
            $string   = htmlentities($bad_char, ENT_IGNORE, 'UTF-8');
            $bad_text = html_entity_decode($string, ENT_IGNORE, 'ISO-8859-1');
            $returned = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($bad_text);

            $this->assertEqual($returned, $replace);
        }
     }
}
?>
