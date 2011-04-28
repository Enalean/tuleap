<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

require_once('HTML_Element_Selectbox.class.php');

/**
 * Define an html selectbox field for scale (day, week, month, year)
 */
class HTML_Element_Selectbox_Scale extends HTML_Element_Selectbox {

    public function __construct($label, $name, $value, $with_none = false, $onchange = "",$desc="") {
        parent::__construct($label, $name, $value, $with_none, $onchange, $desc);
        
        foreach (array('day', 'week', 'month', 'year') as $scale) {
            $selected = $this->value == $scale;
            $this->addOption(new HTML_Element_Option($GLOBALS['Language']->getText('plugin_graphontrackers_date_scale',$scale), $scale, $selected));
        }
    }
}
?>
