<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codex Team.
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
 */

require_once('HTML_Element.class.php');
require_once('HTML_Element_Option.class.php');

/**
 * Define a html selectbox
 */
class HTML_Element_Selectbox extends HTML_Element {
    protected $options;
    protected $onchange;
    public function __construct($label, $name, $value, $with_none = false, $onchange = "", $desc="") {
        parent::__construct($label, $name, $value, $desc);
        $this->options = array();
        
        $this->onchange = $onchange;
        if ($with_none) {
            $this->addOption(new HTML_Element_Option($GLOBALS['Language']->getText('global', 'none_dashed'), "", ($this->value === "" || $this->value === null)));
        }
    }
    protected function renderValue() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '<select id="'. $this->id .'" name='.  $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML) .'" ';
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
