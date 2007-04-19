<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

require_once('Docman_Document.class.php');

class Docman_Empty extends Docman_Document {
    
    function Docman_Empty($data = null) {
        parent::Docman_Document($data);
    }

    function accept(&$visitor, $params = array()) {
        return $visitor->visitEmpty($this, $params);
    }

    function toRow() {
        $row = parent::toRow();
        $row['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_EMPTY;
        return $row;
    }

}

?>
