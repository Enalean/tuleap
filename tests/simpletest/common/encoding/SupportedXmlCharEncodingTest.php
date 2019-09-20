<?php
/**
 * Copyright (c) Enalean, 2012-2016. All rights reserved
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

class Encoding_SupportedXmlCharEncoding_getXMLCompatibleStringTest extends TuleapTestCase
{

    public function itStripsVerticalSpaces()
    {
        $bad_text = 'blockingment visiblesLe guidage de';

        $returned = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($bad_text);

        $this->assertEqual($returned, 'blockingment visibles Le guidage de');
    }

    public function itReplacesOtherBadCharacters()
    {
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
           "’"    => '?',
        );

        foreach ($bad_chars as $bad_char => $replace) {
            //changing the encoding to ISO-8859-1 via mb_convert_encoding. This will result in unorthodox chars
            $str      = mb_convert_encoding($bad_char, 'ISO-8859-15', 'UTF-8');
            $returned = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($str);

            $this->assertEqual($returned, $replace);
        }
    }

    public function itDoesntRemoveGoodChars()
    {
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789¶²&é"\'(-è_çà)=~#{[|`\^@]}£$ù%*µ,;:!?./§\'<>œÇêÊàÀÉ`È¡';
        $this->assertEqual($str, Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($str));
    }

    public function itDoesntRemoveGoodCharsInAnotherEncoding()
    {
        $str = 'REPLACEabcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789¶²&é"\'(-è_çà)=~#{[|`\^@]}£$ù%*µ,;:!?./§\'<>œÇêÊàÀÉ`È¡';
        $str = mb_convert_encoding($str, 'ISO-8859-1', 'ISO-8859-1');

        $this->assertEqual($str, Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($str));
    }

    public function itReplacesBadCharacters()
    {
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
