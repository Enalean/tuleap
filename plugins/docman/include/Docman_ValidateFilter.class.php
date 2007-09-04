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
 * $Id$
 */

class Docman_ValidateFilterFactory {
    function Docman_ValidateFilterFactory() {
        
    }

    function getFromFilter($filter) {
        $f = null;
        if(is_a($filter, 'Docman_FilterDate')) {
            $f = new Docman_ValidateFilterDate($filter);
        }
        return $f;
    }

}

class Docman_ValidateFilter {
    var $filter;
    var $message;
    var $isValid;
    
    function Docman_ValidateFilter($filter) {
        $this->filter = $filter;
        $this->message = '';
        $this->isValid = null; 
    }

    function validate() {
        return $this->isValid;
    }

    function getMessage() {
        return $this->message;
    }
}

class Docman_ValidateFilterDate extends Docman_ValidateFilter {

    function Docman_ValidateFilterDate($filter) {
        parent::Docman_ValidateFilter($filter);
    }

    function validate() {
        if($this->isValid === null) {
            $this->isValid = false;
            if($this->filter->getValue() == '') {
                $this->isValid = true;
            }
            elseif(preg_match('/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/',
                              $this->filter->getValue())) {
                $this->isValid = true;                
            }
            else {
                $today = date("Y-n-j");
                $this->message = $GLOBALS['Language']->getText('plugin_docman', 'filters_date_message', array($this->filter->md->getName(), $today));
            }
        }
        return $this->isValid;
    }
}

?>
