<?php
/**
 * Copyright © STMicroelectronics, 2007. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2007.
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
 * $Id$
 */

require_once('Docman_MetadataSqlQueryChunk.class.php');

class Docman_SqlReportColumnFactory {

    function &getFromColumn($c) {
        $obj = null;
        switch(strtolower(get_class($c))) {
        case 'docman_reportcolumnlocation':
            // No sort on this column
            break;
        default:
            $obj = new Docman_SqlReportColumn($c);
        }
        return $obj;
    }
}

class Docman_SqlReportColumn 
extends Docman_MetadataSqlQueryChunk {
    var $column;

    /**
     *
     */
    function Docman_SqlReportColumn($column) {
        $this->column = $column;
        parent::Docman_MetadataSqlQueryChunk($column->md);
    }

    function getOrderBy() {
        $sql = '';
        
        $sort = $this->column->getSort();
        if($sort !== null) {
            if($sort == PLUGIN_DOCMAN_SORT_ASC) {
                $sql = $this->field.' ASC';
            }
            else {
                $sql = $this->field.' DESC';
            }
        }

        return $sql;
    }
}

?>
