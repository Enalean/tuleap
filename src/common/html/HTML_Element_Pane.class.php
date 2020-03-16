<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

/**
 * Display a pane
 *
 * @todo Try to remove duplication between ProjectUGroup PaneManaget and git pane magement
 */
class HTML_Element_Pane extends HTML_Element
{

    /**
     * Constructor of the class
     *
     * @param Array  $panes   List of panes
     * @param String $active  Active pane
     * @param String $content Content of the active pane
     *
     * @return Void
     */
    public function __construct($panes, $active, $content)
    {
        parent::__construct('', '', '', '');
        $this->panes  = $panes;
        $this->activePane = $active;
        $this->content = $content;
    }

    /**
     * Dispaly the pane
     *
     * @return String
     */
    public function renderValue()
    {
        $html = '<div class="tabbable">';
        $html .= '<ul class="nav nav-tabs">';
        foreach ($this->panes as $pane) {
            $html .= '<li class="' . ($pane['name'] == $this->activePane ? 'active' : '') . '">';
            $html .= '<a href="' . $pane['link'] . '">' . $pane['title'] . '</a></li>';
        }
        $html .= '</ul>';
        $html .= '<div class="tab-content">';
        $html .= '<div class="tab-pane active">';
        $html .= $this->content;
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}
