<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
*
* Originally written by Nicolas Terray, 2006
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
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with CodeX; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* $Id$
*/


/**
* Rule between two dynamic fields
*
* For a tracker, if a source field is selected to a specific value,
* then target field will react, depending of the implementation of the rule.
*
* @abstract
*/
/* abstract */ class ArtifactRule {
    
    var $id;
    var $group_artifact_id;
    var $source_field;
    var $target_field;
    var $source_value;
    
    function ArtifactRule($id, $group_artifact_id, $source_field, $source_value, $target_field) {
        $this->id                = $id;
        $this->group_artifact_id = $group_artifact_id;
        $this->source_field      = $source_field;
        $this->source_value      = $source_value;
        $this->target_field      = $target_field;
    }
}
?>