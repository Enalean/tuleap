<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced;

class InvalidFieldsCollection
{
    /** @var array */
    private $fields_not_exist;

    /** @var array */
    private $fields_not_supported;

    public function __construct()
    {
        $this->fields_not_exist     = array();
        $this->fields_not_supported = array();
    }

    public function addNonexistentField($field_name)
    {
        $this->fields_not_exist[] = $field_name;
    }

    public function addUnsupportedField($field_name)
    {
        $this->fields_not_supported[] = $field_name;
    }

    /**
     * @return boolean
     */
    public function hasInvalidFields()
    {
        return count($this->fields_not_exist) > 0 || count($this->fields_not_supported) > 0;
    }

    /**
     * @return array
     */
    public function getNonexistentFields()
    {
        return $this->fields_not_exist;
    }

    /**
     * @return array
     */
    public function getUnsupportedFields()
    {
        return $this->fields_not_supported;
    }
}
