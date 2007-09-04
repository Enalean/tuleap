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

require_once('Docman_MetadataSqlQueryChunk.class.php');

class Docman_SqlFilterFactory {
    function Docman_SqlFilterFactory() {
        
    }

    function getFromFilter($filter) {
        $f = null;
       
        if(is_a($filter, 'Docman_FilterDateAdvanced')) {
            $f = new Docman_SqlFilterDateAdvanced($filter);
        }
        elseif(is_a($filter, 'Docman_FilterDate')) {
            $f = new Docman_SqlFilterDate($filter);
        } 
        elseif(is_a($filter, 'Docman_FilterGlobalText')) {
            $f = new Docman_SqlFilterGlobalText($filter);
        }
        elseif(is_a($filter, 'Docman_FilterOwner')) {
            $f = new Docman_SqlFilterOwner($filter);
        }
        elseif(is_a($filter, 'Docman_FilterText')) {
            $f = new Docman_SqlFilterText($filter);
        }
        elseif(is_a($filter, 'Docman_FilterListAdvanced')) {
            if(!in_array(0, $filter->getValue())) {
                $f = new Docman_SqlFilterListAdvanced($filter); 
            }
        }
        elseif(is_a($filter, 'Docman_FilterList')) {
            if($filter->getValue() >= 100) {
                $f = new Docman_SqlFilter($filter);
            }
        }       
        return $f;
    }
}

/**
 *
 */
class Docman_SqlFilter 
extends Docman_MetadataSqlQueryChunk {
    var $filter;
    var $isRealMetadata;

    function Docman_SqlFilter($filter) {
        $this->filter = $filter;
        parent::Docman_MetadataSqlQueryChunk($filter->md);
    }

    function getFrom() {
        $tables = array();

        if($this->isRealMetadata) {
            if($this->filter->getValue() !== null &&
               $this->filter->getValue() != '') {
                $tables[] = $this->_getMdvJoin();
            }
        }
        
        return $tables;
    }
    
    function _getSpecificSearchChunk() {
        $stmt = array();
        
        if($this->filter->getValue() !== null &&
           $this->filter->getValue() != '') {
            $stmt[] = $this->field.' = '.DataAccess::quoteSmart($this->filter->getValue());
        }
        
        return $stmt;
    }

    function getWhere() {
        $where = '';

        $whereArray = $this->_getSpecificSearchChunk();
        $where = implode(' AND ', $whereArray);
        
        return $where;
    }
}

/**
 *
 */
class Docman_SqlFilterDate 
extends Docman_SqlFilter {

    function Docman_SqlFilterDate($filter) {
        parent::Docman_SqlFilter($filter);
    }

    // '<'
    function _getEndStatement($value) {
        $stmt = '';
        list($time, $ok) = util_date_to_unixtime($value);
        if($ok) {
            list($year,$month,$day) = util_date_explode($value);
            $time_before = mktime(23, 59, 59, $month, $day-1, $year);
            $stmt = $this->field." <= ".$time_before;
        }
        return $stmt;
    }

    // '=' means that day between 00:00 and 23:59
    function _getEqualStatement($value) {
        $stmt = '';
        list($time, $ok) = util_date_to_unixtime($value);
        if($ok) {
            list($year,$month,$day) = util_date_explode($value);
            $time_end = mktime(23, 59, 59, $month, $day, $year);
            $stmt = $this->field." >= ".$time." AND ".$this->field." <= ".$time_end;
        }
        return $stmt;
    }

    // '>'
    function _getStartStatement($value) {
        $stmt = '';
        list($time, $ok) = util_date_to_unixtime($value);
        if($ok) {
            list($year,$month,$day) = util_date_explode($value);
            $time_after = mktime(0, 0, 0, $month, $day+1, $year);
            $stmt = $this->field." >= ".$time_after;
        }
        return $stmt;
    }

    function _getSpecificSearchChunk() {
        $stmt = array();

        switch($this->filter->getOperator()) {
        case '-1': // '<'
            $s = $this->_getEndStatement($this->filter->getValue());
            if($s != '') {
                $stmt[] = $s;
            }
            break;
        case '0': // '=' means that day between 00:00 and 23:59
            $s = $this->_getEqualStatement($this->filter->getValue());
            if($s != '') {
                $stmt[] = $s;
            }
            break;
        case '1': // '>'
        default:
            $s = $this->_getStartStatement($this->filter->getValue());
            if($s != '') {
                $stmt[] = $s;
            }
            break;
        }

        return $stmt;
    }
}

/**
 *
 */
class Docman_SqlFilterDateAdvanced
extends Docman_SqlFilterDate {

    function Docman_SqlFilterDateAdvanced($filter) {
        parent::Docman_SqlFilterDate($filter);
    }

    function _getSpecificSearchChunk() {
        $stmt = array();

        $startValue = $this->filter->getValueStart();
        $endValue   = $this->filter->getValueEnd();
        if($startValue != '') {
            if($endValue == $startValue) {
                // Equal
                $s = $this->_getEqualStatement($startValue);
                if($s != '') {
                    $stmt[] = $s;
                }
            } else {
                // Lower end
                $s = $this->_getStartStatement($startValue);
                if($s != '') {
                    $stmt[] = $s;
                }
            }
        }
        if($endValue != '') {
            if($endValue != $startValue) {
                // Higher end
                $s = $this->_getEndStatement($endValue);
                if($s != '') {
                    $stmt[] = $s;
                }
            }
        }

        return $stmt;
    }
}

/**
 *
 */
class Docman_SqlFilterOwner 
extends Docman_SqlFilter {

    function Docman_SqlFilterOwner($filter) {
        parent::Docman_SqlFilter($filter);
        $this->field = 'user.user_name';
    }

    function getFrom() {
        $tables = array();
        if($this->filter->getValue() !== null
           && $this->filter->getValue() != '') {
            $tables[] = 'user ON (i.user_id = user.user_id)';
        }
        return $tables;
    }
}

/**
 *
 */
class Docman_SqlFilterText 
extends Docman_SqlFilter {
    var $matchMode;

    function Docman_SqlFilterText($filter) {
        parent::Docman_SqlFilter($filter);
        $this->matchMode = 'IN BOOLEAN MODE';
    }

    function _getSpecificSearchChunk() {
        $stmt = array();
        if($this->filter->getValue() !== null &&
           $this->filter->getValue() != '') {
            $qv = DataAccess::quoteSmart($this->filter->getValue());
            $stmt[] = 'MATCH ('.$this->field.') AGAINST ('.$qv.' '.$this->matchMode.')';
        }
        return $stmt;
    }

}

class Docman_SqlFilterGlobalText
extends Docman_SqlFilterText {
    
    function Docman_SqlFilterGlobalText($filter) {
        parent::Docman_SqlFilterText($filter);
    }

    function getFrom() {
        $tables = array();
        if($this->filter->getValue() !== null &&
           $this->filter->getValue() != '') {
            foreach($this->filter->dynTextFields as $f) {
                $tables[] = $this->_getMdvJoin($f);
            }
        }
        return $tables;
    }

    function _getSpecificSearchChunk() {
        $stmt = array();
        if($this->filter->getValue() !== null &&
           $this->filter->getValue() != '') {
            $qv = DataAccess::quoteSmart($this->filter->getValue());

            $matches[] = 'MATCH (i.title, i.description) AGAINST ('.$qv.' '.$this->matchMode.')';
            $matches[] = 'MATCH (v.label, v.changelog, v.filename) AGAINST ('.$qv.' '.$this->matchMode.')';
            foreach($this->filter->dynTextFields as $f) {
                $matches[] = 'MATCH (mdv_'.$f.'.valueText, mdv_'.$f.'.valueString) AGAINST ('.$qv.' '.$this->matchMode.')';
            }

            $stmt[] = '('.implode(' OR ', $matches).')';
        }
        return $stmt;
    }

}

class Docman_SqlFilterListAdvanced
extends Docman_SqlFilter {

    function Docman_SqlFilterListAdvanced($filter) {
        parent::Docman_SqlFilter($filter);
    }

    function _getSpecificSearchChunk() {
        $stmt = array();
        
        $v = $this->filter->getValue();
        if($v !== null 
           && (count($v) > 0 
               || (count($v) == 1 && $v[0] != '')
               )
           ) {
            $stmt[] = $this->field.' IN ('.implode(',', $this->filter->getValue()).')';
        }
        
        return $stmt;
    }
}

?>
