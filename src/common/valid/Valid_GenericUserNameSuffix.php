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

class Valid_GenericUserNameSuffix extends \Valid_UserNameFormat // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * Append a fake prefix to leverage on username format checking.
     *
     * As we want to validate a suffix, we need to append it to something
     * we now as valid otherwise the check might be invalid. For instance:
     * '-team' is a valid suffix but an invalid UserNameFormat (cannot start
     * by '-'
     * But aaa-team is a valid name at whole.
     */
    public const FAKE_PREFIX = 'aaa';
    public function validate($value)
    {
        return parent::validate(self::FAKE_PREFIX . $value);
    }
}
