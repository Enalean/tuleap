<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * Container for ListOfValues elements.
 */
class Docman_MetadataListOfValuesElement
{
    var $id;
    var $name;
    var $description;
    var $rank;
    var $status;

    function __construct()
    {
        $this->id = null;
        $this->name = null;
        $this->description = null;
        $this->rank = null;
        $this->status = null;
    }

    function setId($v)
    {
        $this->id = $v;
    }
    function getId()
    {
        return $this->id;
    }

    function setName($v)
    {
        $this->name = $v;
    }
    function getName()
    {
        return $this->name;
    }

    function setDescription($v)
    {
        $this->description = $v;
    }
    function getDescription()
    {
        return $this->description;
    }

    function setRank($v)
    {
        $this->rank = $v;
    }
    function getRank()
    {
        return $this->rank;
    }

    function setStatus($v)
    {
        $this->status = $v;
    }
    function getStatus()
    {
        return $this->status;
    }

    function initFromRow($row)
    {
        if (isset($row['value_id'])) {
            $this->id = $row['value_id'];
        }
        if (isset($row['name'])) {
            $this->name = $row['name'];
        }
        if (isset($row['description'])) {
            $this->description = $row['description'];
        }
        if (isset($row['rank'])) {
            $this->rank = $row['rank'];
        }
        if (isset($row['status'])) {
            $this->status = $row['status'];
        }
    }

    public function getMetadataValue(): string
    {
        if ((int)$this->getId() === PLUGIN_DOCMAN_ITEM_STATUS_NONE) {
            return $GLOBALS['Language']->getText('plugin_docman', 'love_special_none_name_key');
        }

        return $this->getName();
    }
}
