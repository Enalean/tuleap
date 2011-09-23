<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('HudsonWidget.class.php');

abstract class HudsonOverviewWidget extends HudsonWidget {
    
    function isUnique() {
        return true;
    }
    
    /**
     * Return widget title
     * 
     * Desactivate global state computation because it might takes very long to load the page.
     * 
     * @see src/common/widget/Widget::getTitle()
     */
    function getTitle($string) {
        $title = '';
        if ($this->_use_global_status == "true") {
            //$this->computeGlobalStatus();
            $title = '<img src="'.$this->_global_status_icon.'" title="'.$this->_global_status.'" alt="'.$this->_global_status.'" /> ';
        }
        $title .= $string; 
        return  $title;
    }
}

?>