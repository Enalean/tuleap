<?php
/**
 * Copyright � STMicroelectronics, 2006. All Rights Reserved.
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

class Docman_HtmlFilterFactory {
    function Docman_HtmlFilterFactory() {
        
    }

    function &getFromFilter(&$filter) {
        $f = null;
        if(is_a($filter, 'Docman_FilterDate')) {
            $f = new Docman_HtmlFilterDate($filter);
        }
        else {
            $f = new Docman_HtmlFilter($filter);
        }
        return $f;
    }

}

class Docman_HtmlFilter {
    var $filter;

    function Docman_HtmlFilter(&$filter) {
        $this->filter =& $filter;
 
    }

    function _valueSelectorHtml($formName) {
        $html = '';
        $value = $this->filter->getValue();
        if($value !== null) {
            $html .= '<input type="hidden" name="'.$this->filter->md->getLabel().'" value="'.$value.'" />';
            $html .= "\n";
        }
        return $html;
    }

    function _sortSelectorHtml($formName) {
        $html = '';
        $sort = $this->filter->getSort();
        if($sort !== null) {
            $html .= '<input type="hidden" name="'.$this->filter->getSortParam().'" value="'.$sort.'" />';
            $html .= "\n";
        }
        return $html;
    }

    function toHtml($formName) {
        $html = '';
        $html .= $this->_valueSelectorHtml($formName);
        $html .= $this->_sortSelectorHtml($formName);
        return $html;
    }
}

class Docman_HtmlFilterDate extends Docman_HtmlFilter {

    function _valueSelectorHtml($formName) {        
        $html  = $this->filter->md->getName().'&nbsp;';
        $html .= html_select_operator($this->filter->getFieldOperatorName(), $this->filter->getOperator());
        $html .= html_field_date($this->filter->getFieldValueName(),
                                 $this->filter->getValue(),
                                 false,
                                 '10',
                                 '10',
                                 $formName,
                                 false);
        $html .= "\n&nbsp;\n";

        return $html;
    }

}

?>
