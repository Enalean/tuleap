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

class Encoding_SupportedXmlCharEncoding
{

    private static $php_supported_encoding_types = array(
        'UTF-8',
        'ISO-8859-1',
        'ISO-8859-5',
        'ISO-8859-15',
    );

   /**
    * @see http://www.w3.org/TR/REC-xml/#charsets
    */
    public static function getXMLCompatibleString($string)
    {
        $clean   = "";
        $current = null;

        if (empty($string)) {
            return $string;
        }

        $string = self::convertToUTF8($string);

        preg_match(
            '/[^\x20-\xD7FF\xE000-\xFFFD\x10000-x10FFFF\x9\xA\xD]/',
            $string,
            $matches
        );

        if (empty($matches) || ! $matches[0]) {
            return $string;
        }

        $length = strlen($string);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($string[$i]);
            if (
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF)) ||
                $current == 0x9 ||
                $current == 0xA ||
                $current == 0xD
            ) {
                $clean .= chr($current);
            } else {
                $clean .= " ";
            }
        }

        return $clean;
    }

   /**
    * @return string UTF-8 string. All unrecognized characters are stripped-out
    */
    private static function convertToUTF8($string)
    {
        $encoding = mb_detect_encoding($string, implode(',', self::$php_supported_encoding_types));

        if ($encoding == 'UTF-8') {
            return $string;
        }

        return mb_convert_encoding($string, 'UTF-8', $encoding);
    }
}
