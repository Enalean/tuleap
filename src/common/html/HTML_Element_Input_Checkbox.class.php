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

require_once('HTML_Element_Input.class.php');
require_once('HTML_Element_Input_Hidden.class.php');
/**
 * Define a html input checkbox field
 */
class HTML_Element_Input_Checkbox extends HTML_Element_Input
{
    public function __construct($label, $name, $value)
    {
        parent::__construct($label, $name, $value);
        if ($this->value) {
            $this->params['checked'] = 'checked';
        }
    }
    public function render()
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html  = '<label class="checkbox inline">';
        $html .= $this->renderValue();
        $html .= ' ' . $hp->purify($this->label, CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</label>';
        return $html;
    }
    public function renderValue()
    {
        $hf = new HTML_Element_Input_Hidden('', $this->name, 0, '');
        $html = $hf->render();
        $html .= parent::renderValue();
        return $html;
    }
    protected function getInputType()
    {
        return 'checkbox';
    }
    /**
     * The value for a checkbox is always 1
     */
    public function getValue()
    {
        return 1;
    }
}
