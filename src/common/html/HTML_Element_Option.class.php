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

/**
 * Define a html select option
 */
class HTML_Element_Option extends HTML_Element {
    protected $selected;
    public function __construct($label, $value, $selected = false) {
        parent::__construct($label, '', $value);
        $this->setSelected($selected);
    }
    public function setSelected($selected = false) {
        $this->selected = $selected ? 'selected="selected"' : '';
    }
    public function render() {
        return $this->renderValue();
    }
    protected function renderValue() {
        $hp = CodeX_HTMLPurifier::instance();
        $html = '<option value="'.  $hp->purify($this->value, CODEX_PURIFIER_CONVERT_HTML) .'" '. $this->selected .'>';
        $html .=  $hp->purify($this->label, CODEX_PURIFIER_CONVERT_HTML) ;
        $html .= '</option>';
        return $html;
    }
}

?>
