<?php
/*
 * Copyright � STMicroelectronics, 2006
 * Originally written by Manuel VACELET, STMicroelectronics, 2006
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
 * 
 *
 */

class Docman_Filter {
    var $name;     
    var $value;
    var $md;
    var $sort;

    function Docman_Filter($md) {  
        $this->value = null;
        $this->md = $md;
        $this->sort = null;
    }
  
    function setValue($v) {
        $this->value = $v;
    }

    function getValue() {
        return $this->value;
    }

    function setSort($s) {
        $this->sort = $s;
    }

    function getSort() {
        return $this->sort;
    }

    function getUrlParameters() {
        $param = array();
        if($this->value !== null) {
            $param[$this->md->getLabel()] = $this->value;
        }
        return $param;
    }

    function getSortParam() {
        $sortParam = null;
        if($this->md !== null) {
            $sortParam = 'sort_'.$this->md->getLabel();
        }
        return $sortParam;
    }

    function _initSortFromRequest($request) {
        $sortparam = $this->getSortParam();
        if($request->exist($sortparam)) {
            $this->setSort($request->get($sortparam));
        }
    }

    function _initFilterFromRequest($request) {
        $param = $this->md->getLabel();
        if($request->exist($param)) {
            $this->setValue($request->get($param));
        }
    }

    function initFromRequest($request) {
        if($this->md !== null) {
            $this->_initSortFromRequest($request);
            $this-> _initFilterFromRequest($request);
        }
    }
}

class Docman_FilterDate extends Docman_Filter {
    var $operator;
    var $field_operator_name;
    var $field_value_name;

    function Docman_FilterDate($md) {
        parent::Docman_Filter($md);
        $this->operator = null;
        if($md !== null) {
            $label = $md->getLabel();
            $this->field_operator_name  = $label.'_operator';
            $this->field_value_name     = $label.'_value';
        }
    }

    function getFieldOperatorName() {
        return $this->field_operator_name;
    }

    function getFieldValueName() {
        return $this->field_value_name;
    }

    function setOperator($v) {
        $this->operator = $v;
    } 

    function getOperator() {
        return $this->operator;
    }

    function getUrlParameters() {
        $param = array();
        if($this->value !== null) {
            $param[$this->field_value_name] = $this->value;
            if($this->operator !== null) {
                $param[$this->field_operator_name] = $this->operator;
            }
        }
        return $param;
    }

    function _initFilterFromRequest(&$request) {
        if($request->exist($this->getFieldValueName()) && $request->get($this->getFieldValueName()) != '') {
            $this->setValue($request->get($this->getFieldValueName()));
            
            if($request->exist($this->getFieldOperatorName())) {
                $this->setOperator($request->get($this->getFieldOperatorName()));
            }
        }
    }
}

?>
