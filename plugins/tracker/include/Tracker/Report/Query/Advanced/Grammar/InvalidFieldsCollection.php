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

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

class InvalidFieldsCollection
{
    /** @var array */
    private $fields_not_exist;

    /** @var array */
    private $fields_not_supported;

    public function __construct($fields_not_exist, $fields_not_supported)
    {
        $this->fields_not_exist     = $fields_not_exist;
        $this->fields_not_supported = $fields_not_supported;
    }

    /**
     * @return boolean
     */
    public function hasNonexistentFields()
    {
        return ! empty($this->fields_not_exist);
    }

    /**
     * @return boolean
     */
    public function hasUnsupportedFields()
    {
        return ! empty($this->fields_not_supported);
    }

    /**
     * @return boolean
     */
    public function hasNoErrors()
    {
        return ! $this->hasNonexistentFields() && ! $this->hasUnsupportedFields();
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

    /**
     * @return string
     */
    public function getNonexistentFieldsString()
    {
        return implode("', '", $this->fields_not_exist);
    }

    /**
     * @return string
     */
    public function getUnsupportedFieldsString()
    {
        return implode("', '", $this->fields_not_supported);
    }
}
