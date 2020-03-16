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

require_once('HTML_Element.class.php');
require_once('HTML_Element_Option.class.php');

/**
 * Define a html selectbox
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class HTML_Element_Selectbox extends HTML_Element
{
    protected $options;
    protected $onchange;
    public function __construct($label, $name, $value, $with_none = false, $onchange = "", $desc = "")
    {
        parent::__construct($label, $name, $value, $desc);
        $this->options = array();

        $this->onchange = $onchange;
        if ($with_none) {
            $this->addOption(new HTML_Element_Option(dgettext('tuleap-core', '-- None --'), "", ($this->value === "" || $this->value === null)));
        }
    }
    public function renderValue()
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '<select id="' . $this->id . '" name="' .  $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML) . '" ';
        if ($this->onchange) {
            $html .= 'onchange="' . $this->onchange . '" ';
        }
        $html .= '>';
        foreach ($this->options as $o) {
            $html .= $o->render();
        }
        $html .= '</select>';
        return $html;
    }
    public function addOption(HTML_Element_Option $option)
    {
        $this->options[] = $option;
    }

    /**
     * Set the id
     *
     * @param String $id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Add multiple options from an array
     *
     * @param Array  $options  List of options to add
     * @param String $selected The option to be selected by default
     *
     * @return void
     */
    public function addMultipleOptions($options, $selected)
    {
        foreach ($options as $value => $label) {
            $this->addOption(new HTML_Element_Option($label, $value, $value == $selected));
        }
    }
}
