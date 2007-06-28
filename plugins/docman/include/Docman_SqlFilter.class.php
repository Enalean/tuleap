<?php
/**
 * Copyright © STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
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

class Docman_SqlFilterFactory {
    function Docman_SqlFilterFactory() {
        
    }

    function &getFromFilter(&$filter) {
        $f = null;
       
        if($filter->md->getLabel() == 'owner') {
            $f = new Docman_SqlFilterOwner($filter);
        }
        else {
            switch($filter->md->getType()) {
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                $f = new Docman_SqlFilterDate($filter);
                break;
                
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                $f = new Docman_SqlFilter($filter);
                break;
            }
        }
        
        return $f;
    }

}

class Docman_SqlFilter {
    var $filter;
    var $isRealMetadata;

    function Docman_SqlFilter(&$filter) {
        $this->filter =& $filter;
        $this->isRealMetadata = Docman_MetadataFactory::isRealMetadata($this->filter->md->getLabel());

        if($this->isRealMetadata) {
            switch($filter->md->getType()) {
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                $this->field = 'mdv.valueText';
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                $this->field = 'mdv.valueString';
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                $this->field = 'mdv.valueDate';
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                $this->field = 'mdv.valueInt';
                break;
            }
        }
        else {
            $this->field = 'i.'.$this->filter->md->getLabel();
        }
    }

    function getFromClause() {
        $tables = array();

        if($this->isRealMetadata) {
            if($this->filter->getValue() !== null) {
                $tables[] = 'plugin_docman_metadata_value AS mdv';
                $tables[] = 'plugin_docman_metadata AS md';
            }
        }
        
        return $tables;
    }

    
    function _getCommonSearchChunk() {
        $stmt = array();
        
        if($this->isRealMetadata) {
            if($this->filter->getValue() !== null) {
                $stmt[] = 'md.label = '.DataAccess::quoteSmart($this->filter->md->getLabel());
                $stmt[] = 'mdv.field_id = md.field_id';
                $stmt[] = 'mdv.item_id = i.item_id';
            }
        }
        
        return $stmt;
    }
    
    function _getSpecificSearchChunk() {
        $stmt = array();
        
        if($this->filter->getValue() !== null) {
            $stmt[] = $this->field.' = '.DataAccess::quoteSmart($this->filter->getValue());
        }
        
        return $stmt;
    }

    function getWhereClause() {
        $where = '';

        $whereArray = array_merge($this->_getCommonSearchChunk(), 
                                  $this->_getSpecificSearchChunk());
        $where = implode(' AND ', $whereArray);
        
        return $where;
    }

    function getOrderClause() {
        $sql = '';

        $sort = $this->filter->getSort();
        if($sort !== null) {
            if($sort == '1') {
                $sql = $this->field.' ASC';
            }
            else {
                $sql = $this->field.' DESC';
            }
        }

        return $sql;
    }
}

class Docman_SqlFilterDate extends Docman_SqlFilter {

    function Docman_SqlFilterDate(&$filter) {
        parent::Docman_SqlFilter($filter);
    }

    function _getSpecificSearchChunk() {
        $stmt = array();

        $value = $this->filter->getValue();
        list($time, $ok) = util_date_to_unixtime($value);

        if($ok) {
            list($year,$month,$day) = util_date_explode($value);
            switch($this->filter->getOperator()) {
            case '-1':
                // '<'
                $time_before = mktime(23, 59, 59, $month, $day-1, $year);
                $stmt[] = $this->field." <= ".$time_before;
                break;
            case '0':
                // '=' means that day between 00:00 and 23:59
                $time_end = mktime(23, 59, 59, $month, $day, $year);
                $stmt[] = $this->field." >= ".$time." AND ".$this->field." <= ".$time_end;
                break;
            case '1':
            default:
                // '>'
                $time_after = mktime(0, 0, 0, $month, $day+1, $year);
                $stmt[] = $this->field." >= ".$time_after;
                break;
            }
            
        }
       
        return $stmt;
    }
}

class Docman_SqlFilterOwner extends Docman_SqlFilter {

    function Docman_SqlFilterOwner(&$filter) {
        parent::Docman_SqlFilter($filter);
        $this->field = 'user.user_name';
    }

    function getFromClause() {
        $tables = array();
        if($this->filter->getSort() !== null) {
            $tables[] = 'user';
        }
        return $tables;
    }

     function getWhereClause() {
        $where = '';

        if($this->filter->getSort() !== null) {
            $whereArray = array();
            $whereArray[] = 'user.user_id = i.user_id';
            $where = implode(' AND ', $whereArray);
        }
        
        return $where;
    }
}

?>
