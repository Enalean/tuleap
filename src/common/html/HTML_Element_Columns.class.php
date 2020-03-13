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

/**
 * Define a multi-column component
 */
class HTML_Element_Columns extends HTML_Element
{
    protected $components;
    protected $desc;
    /**
     * Constructor
     *
     * Accepts any HTML_Element as parameter.
     * Each HTML_Element will be displayed in its own column
     *
     * Usage:
     * <code>
     * $c = new ComponentsHTML_Columns(
     *             new HTML_Element_Input_Text(),
     *             new HTML_Element_Input_Text(),
     *             new HTML_Element_Textarea(),
     * );
     * </code>
     */
    public function __construct()
    {
        parent::__construct(null, null, null);
        $this->components = func_get_args();
    }
    public function render()
    {
        $html = '<table id="' . $this->id . '" ><tr>';
        foreach ($this->components as $c) {
            $html .= '<td>' . $c->render() . '</td>';
        }
        $html .= '</tr></table>';
        return $html;
    }
}
