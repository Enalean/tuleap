<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('PropertyDescriptor.class.php');

/**
 * PropertyDescriptor
 */
class PropertyDescriptor
{

    public $name;
    public $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->setValue($value);
    }

    public function getName()
    {
        return $this->name;
    }
    public function getValue()
    {
        return $this->value;
    }
    public function setValue($value)
    {
        $this->value = $value;
    }
}
