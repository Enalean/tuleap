<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Encoding_SupportedXmlCharEncoding_getXMLCompatibleStringTest extends TestCase
{

    public function testItStripsVerticalSpaces(): void
    {
        $bad_text = 'blockingment visiblesLe guidage de';

        $returned = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($bad_text);

        $this->assertEquals($returned, 'blockingment visibles Le guidage de');
    }

    public function testItReplacesOtherBadCharacters(): void
    {
        $bad_chars =  [
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
        ];

        foreach ($bad_chars as $bad_char => $replace) {
            //changing the encoding to ISO-8859-1 via mb_convert_encoding. This will result in unorthodox chars
            $str      = mb_convert_encoding($bad_char, 'ISO-8859-15', 'UTF-8');
            $returned = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($str);

            $this->assertEquals($replace, $returned);
        }
    }

    public function testItDoesntRemoveGoodChars(): void
    {
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789¶²&é"\'(-è_çà)=~#{[|`\^@]}£$ù%*µ,;:!?./§\'<>œÇêÊàÀÉ`È¡';
        $this->assertEquals($str, Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($str));
    }

    public function testItDoesntRemoveGoodCharsInAnotherEncoding(): void
    {
        $str = 'REPLACEabcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789¶²&é"\'(-è_çà)=~#{[|`\^@]}£$ù%*µ,;:!?./§\'<>œÇêÊàÀÉ`È¡';
        $str = mb_convert_encoding($str, 'ISO-8859-1', 'ISO-8859-1');

        $this->assertEquals($str, Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($str));
    }

    public function testItReplacesBadCharacters()
    {
        $bad_chars =  [
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
        ];

        foreach ($bad_chars as $bad_char => $replace) {
            $string   = htmlentities($bad_char, ENT_IGNORE, 'UTF-8');
            $bad_text = html_entity_decode($string, ENT_IGNORE, 'ISO-8859-1');
            $returned = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($bad_text);

            $this->assertEquals($replace, $returned);
        }
    }
}
