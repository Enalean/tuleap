<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
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
 * 
 */


/**
 * Metadata container
 *
 * This class aims to give all informations about docmanwatermark's metadata, even if
 * the metadata are hard coded (such as title, create_date, ...).
 */
class DocmanWatermark_Metadata {
    var $id;
    var $groupId;

    function __construct() {
        $this->id = null;
        $this->groupId = null;
    }

    //{{{ Accessors
    function setId($v) {
        $this->id = $v;
    }
    function getId() {
        return $this->id;
    }

    function setGroupId($v) {
        $this->groupId = $v;
    }
    function getGroupId() {
        return $this->groupId;
    }

    ///}}} Accessors

}

?>
