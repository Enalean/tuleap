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

/**
 * Check that given value is a valid signed 32 bits decimal integer.
 */
class Rule_Int extends \Rule // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * Check the format according to PHP definition of a decimal integer.
     * @see http://php.net/int
     * @access private
     */
    public function checkFormat($val)
    {
        if (\preg_match('/^([+-]?[1-9][0-9]*|[+-]?0)$/', $val)) {
            return \true;
        } else {
            return \false;
        }
    }
    public function isValid($val)
    {
        // Need to check with the regexp because of octal form '0123' that is
        // equal to '123' with string '==' comparison.
        if ($this->checkFormat($val)) {
            // Check (-2^31;2^31-1) range
            if (\strval(\intval($val)) == $val) {
                return \true;
            } else {
                return \false;
            }
        } else {
            return \false;
        }
    }
}
