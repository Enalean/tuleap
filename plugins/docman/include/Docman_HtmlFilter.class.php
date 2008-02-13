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

class Docman_HtmlFilterFactory {

    function Docman_HtmlFilterFactory() {
        
    }

    function getFromFilter($filter) {
        $f = null;
        if(is_a($filter, 'Docman_FilterDateAdvanced')) {
            $f = new Docman_HtmlFilterDateAdvanced($filter);
        }
        elseif(is_a($filter, 'Docman_FilterDate')) {
            $f = new Docman_HtmlFilterDate($filter);
        }
        elseif(is_a($filter, 'Docman_FilterListAdvanced')) {
            $f = new Docman_HtmlFilterListAdvanced($filter);
        }
        elseif(is_a($filter, 'Docman_FilterList')) {
            $f = new Docman_HtmlFilterList($filter);
        }
        elseif(is_a($filter, 'Docman_FilterText')) {
            $f = new Docman_HtmlFilterText($filter);
        }
        elseif(is_a($filter, 'Docman_FilterOwner')) {
            $f = new Docman_HtmlFilterText($filter);
        }
        else {
            $f = new Docman_HtmlFilter($filter);
        }
        return $f;
    }

}

class Docman_HtmlFilter {
    var $filter;
    var $hp;

    function Docman_HtmlFilter($filter) {
        $this->filter = $filter;
        $this->hp =& CodeX_HTMLPurifier::instance();
    }

    function _fieldName() {
        $html = $this->hp->purify($this->filter->md->getName());
        return $html;
    }

    function _valueSelectorHtml($formName) {
        $html = '';
        $value = $this->filter->getValue();
        if($value !== null) {
            $html .= '<input type="hidden" name="'.$this->filter->md->getLabel().'" value="'.$this->hp->purify($value).'" />';
            $html .= "\n";
        }
        return $html;
    }

    function toHtml($formName, $trashLinkBase) {
        $trashLink = '';
        if($trashLinkBase) {
            $trashLink = $trashLinkBase.$this->filter->md->getLabel();
            $trashWarn = 'Are your sure you want to remove from filter list?';
            $trashAlt  = '';
            $trashLink = html_trash_link($trashLink, $trashWarn, $trashAlt);
        }

        $html = '<tr>';
        $html .= '<td>';
        $html .= $trashLink;
        $html .= '&nbsp;';
        $html .= $this->_fieldName();
        $html .= ': ';
        $html .= '</td>';
        $html .= '<td>';
        $html .= $this->_valueSelectorHtml($formName);
        $html .= '</td>';
        $html .= '</tr>';
        $html .= "\n";
        return $html;
    }
}

class Docman_HtmlFilterDate extends Docman_HtmlFilter {

    function Docman_HtmlFilterDate($filter) {
        parent::Docman_HtmlFilter($filter);
    }
    
    function _valueSelectorHtml($formName) {
        $html = '';
        $html .= html_select_operator($this->filter->getFieldOperatorName(), $this->filter->getOperator());
        $html .= html_field_date($this->filter->getFieldValueName(),
                                 $this->filter->getValue(),
                                 false,
                                 '10',
                                 '10',
                                 $formName,
                                 false);
        return $html;
    }
}

class Docman_HtmlFilterDateAdvanced 
extends Docman_HtmlFilterDate {

    function Docman_HtmlFilterDateAdvanced($filter) {
        parent::Docman_HtmlFilterDate($filter);
    }

    function _valueSelectorHtml($formName) {
        $html = '';

        $html .= $GLOBALS['Language']->getText('plugin_docman', 'filters_html_date_start');
        $html .= '&nbsp;';
        $html .= html_field_date($this->filter->getFieldStartValueName(),
                                 $this->filter->getValueStart(),
                                 false,
                                 '10',
                                 '10',
                                 $formName,
                                 false);
        $html .= '&nbsp;';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'filters_html_date_end');
        $html .= '&nbsp;';
        $html .= html_field_date($this->filter->getFieldEndValueName(),
                                 $this->filter->getValueEnd(),
                                 false,
                                 '10',
                                 '10',
                                 $formName,
                                 false);
        return $html;
    }
}


class Docman_HtmlFilterList extends Docman_HtmlFilter {
    
    function Docman_HtmlFilterList($filter) {
        parent::Docman_HtmlFilter($filter);
    }

    function buildSelectBox($vals, $txts) {
        $html = html_build_select_box_from_arrays($vals, $txts, $this->filter->md->getLabel(), $this->filter->getValue(), false, '', true, $GLOBALS['Language']->getText('global', 'any'));
        return $html;
    }

    function _valueSelectorHtml($formName=0) {
        $vIter =& $this->filter->md->getListOfValueIterator();
        $vIter->rewind();
        while($vIter->valid()) {
            $e =& $vIter->current();
            
            if($e->getStatus() == 'A' 
               || $e->getStatus() == 'P') {                
                $vals[]  = $e->getId();
                $txts[] = Docman_MetadataHtmlList::_getElementName($e);
            }
            
            $vIter->next();
        }
        
        $html = $this->buildSelectBox($vals, $txts);
        return $html;
    }
}

class Docman_HtmlFilterListAdvanced 
extends Docman_HtmlFilterList {
    
    function Docman_HtmlFilterListAdvanced($filter) {
        parent::Docman_HtmlFilterList($filter);
    }

    function buildSelectBox($vals, $txts) {
        
        $html = html_build_select_box_from_arrays($vals, $txts, $this->filter->md->getLabel(), $this->filter->getValue(), false, '', true, $GLOBALS['Language']->getText('global', 'any'));
        return $html;
    }

}

class Docman_HtmlFilterText extends Docman_HtmlFilter {

    function Docman_HtmlFilterText($filter) {
        parent::Docman_HtmlFilter($filter);
    }

    function _valueSelectorHtml($formName=0) {
        $html = '';
        $html .= '<input type="text" name="'.$this->filter->md->getLabel().'" value="'.$this->hp->purify($this->filter->getValue()).'" class="text_field"/>';
        return $html;
    }
}

?>
