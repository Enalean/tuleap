<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

require_once('Docman_FilterFactory.class.php');

class Docman_Report
{
    var $id;
    var $name;
    var $title;
    var $groupId;
    var $userId;
    var $itemId;
    var $scope;
    var $isDefault;
    var $advancedSearch;
    var $description;
    var $image;

    var $filters;
    var $columns;

    function __construct()
    {
        $this->id             = null;
        $this->name           = null;
        $this->title          = null;
        $this->groupId        = null;
        $this->userId         = null;
        $this->itemId         = null;
        $this->scope          = 'I';
        $this->isDefault      = null;
        $this->advancedSearch = null;
        $this->description    = null;
        $this->image          = null;

        $this->filters = array();
        $this->columns = array();
    }

    function setId($i)
    {
        $this->id = $i;
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

    function setTitle($v)
    {
        $this->title = $v;
    }
    function getTitle()
    {
        return $this->title;
    }

    function setGroupId($g)
    {
        $this->groupId = $g;
    }
    function getGroupId()
    {
        return $this->groupId;
    }

    function setUserId($v)
    {
        $this->userId = $v;
    }
    function getUserId()
    {
        return $this->userId;
    }

    function setItemId($v)
    {
        $this->itemId = $v;
    }
    function getItemId()
    {
        return $this->itemId;
    }

    function setScope($v)
    {
        $this->scope = $v;
    }
    function getScope()
    {
        return $this->scope;
    }

    function setIsDefault($v)
    {
        $this->isDefault = $v;
    }
    function getIsDefault()
    {
        return $this->isDefault;
    }

    function setAdvancedSearch($v)
    {
        $this->advancedSearch = $v;
    }
    function getAdvancedSearch()
    {
        return $this->advancedSearch;
    }

    function setDescription($v)
    {
        $this->description = $v;
    }
    function getDescription()
    {
        return $this->description;
    }

    function setImage($v)
    {
        $this->image = $v;
    }
    function getImage()
    {
        return $this->image;
    }

    function initFromRow($row)
    {
        if (isset($row['report_id'])) {
            $this->id = $row['report_id'];
        }
        if (isset($row['name'])) {
            $this->name = $row['name'];
        }
        if (isset($row['title'])) {
            $this->title = $row['title'];
        }
        if (isset($row['group_id'])) {
            $this->groupId = $row['group_id'];
        }
        if (isset($row['user_id'])) {
            $this->userId = $row['user_id'];
        }
        if (isset($row['item_id'])) {
            $this->itemId = $row['item_id'];
        }
        if (isset($row['scope'])) {
            $this->scope = $row['scope'];
        }
        if (isset($row['is_default'])) {
            $this->isDefault = $row['is_default'];
        }
        if (isset($row['advanced_search'])) {
            $this->advancedSearch = $row['advanced_search'];
        }
        if (isset($row['description'])) {
            $this->description = $row['description'];
        }
        if (isset($row['image'])) {
            $this->image = $row['image'];
        }
    }

    function addColumn(&$c)
    {
        $this->columns[] = $c;
    }

    function &getColumnIterator()
    {
        $i = new ArrayIterator($this->columns);
        return $i;
    }

    function getFiltersArray()
    {
        return $this->filters;
    }
    function setFiltersArray($a)
    {
        $this->filters = $a;
    }

    function addFilter(&$f)
    {
        $this->filters[] = $f;
    }

    function &getFilterIterator()
    {
        $i = new ArrayIterator($this->filters);
        return $i;
    }

    function getUrlParameters()
    {
        $param = array();
        // Report Id
        /*if($this->getId() !== null
           && $this->getId() > 0) {
            $param['report_id'] = $this->getId();
        }*/

        // Advanced search
        if ($this->advancedSearch) {
            $param['advsearch'] = 1;
        }
        return $param;
    }

    function getGlobalSearchMetadata()
    {
        $filterFactory = new Docman_FilterFactory($this->groupId);
        return $filterFactory->getGlobalSearchMetadata();
    }

    function getItemTypeSearchMetadata()
    {
        $filterFactory = new Docman_FilterFactory($this->groupId);
        return $filterFactory->getItemTypeSearchMetadata();
    }
}
