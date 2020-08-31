<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

class ValidFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * If $validator is an instance of a Validator, do nothing and returns it
     * If $validator is a string and a validator exists (Valid_String for 'string', Valid_UInt for 'uint', ...) then creates an instance and returns it
     * Else returns null
     */
    public static function getInstance($validator, $key = \null)
    {
        if ($validator instanceof \Valid) {
            return $validator;
        }
        if (! \is_string($validator)) {
            return \null;
        }

        switch (strtolower($validator)) {
            case 'uint':
                return new Valid_UInt($key);
            case 'int':
                return new Valid_Int($key);
            case 'text':
                return new Valid_Text($key);
            case 'string':
                return new Valid_String($key);
            case 'groupid':
                return new Valid_GroupId($key);
            case 'localuri':
                return new Valid_LocalURI($key);
            default:
                $class_name = 'Valid_' . $validator;
                if (! \class_exists($class_name)) {
                    return \null;
                }
                return new $class_name($key);
        }
    }
}
