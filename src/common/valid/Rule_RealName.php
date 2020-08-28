<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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

class Rule_RealName extends \Rule // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function isValid($string)
    {
        if ($this->containsBackslashCharacter($string) || $this->containsNonPrintingCharacter($string)) {
            return \false;
        }
        return \true;
    }
    private function containsBackslashCharacter($string)
    {
        return \strpos($string, "\\") !== \false;
    }
    private function containsNonPrintingCharacter($string)
    {
        for ($i = 0; $i < \strlen($string); $i++) {
            if ($this->isNonPrintingCharacter($string[$i])) {
                return \true;
            }
        }
        return \false;
    }
    private function isNonPrintingCharacter($char)
    {
        return \hexdec(\bin2hex($char)) < 32;
    }
}
