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

abstract class Rule // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * @access private
     */
    public $error;
    /**
     * Constructor
     */
    public function __construct()
    {
    }
    /**
     * Check if $val is a valid not.
     *
     * @param String $val Value to check.
     * @return bool
     */
    abstract public function isValid($val);
    /**
     * Default error message if rule is not apply on value.
     *
     * @param string $key
     * @return string
     */
    public function getErrorMessage($key = '')
    {
        return $this->error;
    }
}
