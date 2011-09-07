<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('HTML_Element.class.php');
require_once('HTML_Element_Option.class.php');

/**
 * Define a html selectbox from an array
 */
class HTML_Element_Selectbox_Array extends HTML_Element {
    protected $options;
    protected $onchange;
    public function __construct($values, $id, $label, $name, $value, $with_none = false, $onchange = "", $desc="", $selected = null, $sameval = false) {
        parent::__construct($label, $name, $value, $desc);
        $this->id = $id;
        $this->options = array();

        $this->onchange = $onchange;
        if ($with_none) {
            $this->addOption(new HTML_Element_Option($GLOBALS['Language']->getText('global', 'none_dashed'), "", ($this->value === "" || $this->value === null)));
        }
        if ($sameval) {
            foreach ($values as $text) {
                $this->addOption(new HTML_Element_Option($text, $text, $text == $selected));
            }
        } else{
            foreach ($values as $key => $text) {
                $this->addOption(new HTML_Element_Option($text, $key, $key == $selected));
            }
        }
    }
    function renderValue() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '<select id="'. $this->id .'" name="'.  $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML) .'" ';
        if ($this->onchange) {
            $html .= 'onchange="'. $this->onchange .'" ';
        }
        $html .= '>';
        foreach($this->options as $o) {
            $html .= $o->render();
        }
        $html .= '</select>';
        return $html;
    }
    public function addOption(HTML_Element_Option $option) {
        $this->options[] = $option;
    }
}

?>