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
    public $id;
    public $name;
    public $title;
    public $groupId;
    public $userId;
    public $itemId;
    public $scope;
    public $isDefault;
    public $advancedSearch;
    public $description;
    public $image;

    public $filters;
    public $columns;

    public function __construct()
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

    public function setId($i)
    {
        $this->id = $i;
    }
    public function getId()
    {
        return $this->id;
    }

    public function setName($v)
    {
        $this->name = $v;
    }
    public function getName()
    {
        return $this->name;
    }

    public function setTitle($v)
    {
        $this->title = $v;
    }
    public function getTitle()
    {
        return $this->title;
    }

    public function setGroupId($g)
    {
        $this->groupId = $g;
    }
    public function getGroupId()
    {
        return $this->groupId;
    }

    public function setUserId($v)
    {
        $this->userId = $v;
    }
    public function getUserId()
    {
        return $this->userId;
    }

    public function setItemId($v)
    {
        $this->itemId = $v;
    }
    public function getItemId()
    {
        return $this->itemId;
    }

    public function setScope($v)
    {
        $this->scope = $v;
    }
    public function getScope()
    {
        return $this->scope;
    }

    public function setIsDefault($v)
    {
        $this->isDefault = $v;
    }
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    public function setAdvancedSearch($v)
    {
        $this->advancedSearch = $v;
    }
    public function getAdvancedSearch()
    {
        return $this->advancedSearch;
    }

    public function setDescription($v)
    {
        $this->description = $v;
    }
    public function getDescription()
    {
        return $this->description;
    }

    public function setImage($v)
    {
        $this->image = $v;
    }
    public function getImage()
    {
        return $this->image;
    }

    public function initFromRow($row)
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

    public function addColumn(&$c)
    {
        $this->columns[] = $c;
    }

    public function &getColumnIterator()
    {
        $i = new ArrayIterator($this->columns);
        return $i;
    }

    public function getFiltersArray()
    {
        return $this->filters;
    }
    public function setFiltersArray($a)
    {
        $this->filters = $a;
    }

    public function addFilter(&$f)
    {
        $this->filters[] = $f;
    }

    public function &getFilterIterator()
    {
        $i = new ArrayIterator($this->filters);
        return $i;
    }

    public function getUrlParameters()
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

    public function getGlobalSearchMetadata()
    {
        $filterFactory = new Docman_FilterFactory($this->groupId);
        return $filterFactory->getGlobalSearchMetadata();
    }

    public function getItemTypeSearchMetadata()
    {
        $filterFactory = new Docman_FilterFactory($this->groupId);
        return $filterFactory->getItemTypeSearchMetadata();
    }
}
