<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


class Docman_Validator
{
    public $_errors = [];
    public function addError($error)
    {
        $this->_errors[] = $error;
    }
    public function getErrors()
    {
        return $this->_errors;
    }
    public function isValid()
    {
        return count($this->_errors) ? false : true;
    }
}
class Docman_ValidatePresenceOf extends Docman_Validator
{
    public function __construct($data, $field, $msg)
    {
        if (!$data || !isset($data[$field]) || trim($data[$field]) == '') {
            $this->addError($msg);
        }
    }
}

class Docman_ValidateValueNotEmpty extends Docman_Validator
{
    public function __construct($value, $msg)
    {
        if (!$value || $value === null || $value == '') {
            $this->addError($msg);
        }
    }
}
