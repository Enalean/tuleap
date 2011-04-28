<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
 * 
 * Updated by Nicolas Terray, 2008, Xerox Codendi Team
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/html/HTML_Element_Selectbox.class.php');

/**
 * Define an html selectbox field for method (age, time evolution)
 */
class HTML_Element_Method_Selectbox extends HTML_Element_Selectbox {

    public function __construct($label, $name, $value) {
        parent::__construct($label, $name, $value);
        foreach (array('age', 'time_evolution') as $o) {
            $selected = $this->value == $o;
            $this->addOption(new HTML_Element_Option($GLOBALS['Language']->getText('plugin_graphontrackers_line_method','method_'.$o), $o, $selected));
        }
    }
}

?>
