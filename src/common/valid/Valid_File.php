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

/**
 * Check uploaded file validity.
 */
class Valid_File extends \Valid // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * Is uploaded file empty or not.
     *
     * @param Array One entry of $_FILES
     */
    public function isEmptyValue($file)
    {
        if (! \is_array($file)) {
            return \false;
        } elseif (parent::isEmptyValue($file['name'])) {
            return \false;
        } else {
            return \true;
        }
    }
    /**
     * Check rules on given file.
     *
     * @param  Array  $value $_FILES superarray.
     * @param  String Index of file to check in $_FILES array.
     * @return bool
     */
    public function validate($value, $index = '')
    {
        if (\is_array($value) && isset($value[$index])) {
            $this->addRule(new \Rule_File());
            return parent::validate($value[$index]);
        } elseif ($this->isRequired) {
            return \false;
        } else {
            return \true;
        }
    }
}
