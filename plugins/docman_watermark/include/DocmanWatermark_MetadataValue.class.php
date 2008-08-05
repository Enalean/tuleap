<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * 
 * Originally written by Mahmoud MAALEJ, 2008.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * 
 */


/**
 * Metadata container
 *
 * This class aims to give all informations about docmanwatermark's metadata Values, even if
 * the metadata are hard coded (such as title, create_date, ...).
 */
class DocmanWatermark_MetadataValue {
    var $valueId;
    var $watermark;

    function DocmanWatermark_MetadataValue() {
        $this->valueId = null;
        $this->watermark = null;
    }

    //{{{ Accessors
    function setValueId($v) {
        $this->valueId = $v;
    }
    function getValueId() {
        return $this->valueId;
    }

    function setWatermark($v) {
        $this->watermark = $v;
    }
    function getWatermark() {
        return $this->watermark;
    }

    ///}}} Accessors

    function initFromRow($row) {
        if(isset($row['value_id'])) $this->valueId = $row['value_id'];
        if(isset($row['watermark'])) $this->watermark = $row['watermark'];
    }

}

?>
