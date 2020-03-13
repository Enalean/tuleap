<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of Tuleap.
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

class Docman_View_ItemDetails
{

    public $sections;
    public $item;
    public $current_section;
    public $url;

    public function __construct($item, $url)
    {
        $this->item             = $item;
        $this->url              = $url;
        $this->current_section  = null;
        $this->sections         = array();
    }

    public function addSection($section)
    {
        $this->sections[$section->getId()] = $section;
        if (!$this->current_section && count($this->sections) == 1) {
            $this->setCurrentSection($section->getId());
        }
    }
    public function setCurrentSection($id_section)
    {
        $this->current_section = $id_section;
    }
    public function fetch()
    {
        $html = '';

        $html .= '<br />';
        if (count($this->sections)) {
            $html .= '<ul class="docman_properties_navlist">';
            foreach ($this->sections as $section) {
                $html .= '<li><a href="' . $this->url . '&amp;action=details&amp;id=' . $this->item->getId() . '&amp;section=' . $section->getId() . '"';
                if ($section->getId() == $this->current_section) {
                    $html .= ' class="docman_properties_navlist_current" ';
                }
                $html .= '>' . $section->getTitle() . '</a></li>';
            }
            $html .= '</ul>';
            $html .= '<div class="docman_properties_content">';
            $html .= $this->sections[$this->current_section]->getContent();
            $html .= '</div>';
        }
        return $html;
    }
    public function display()
    {
        echo $this->fetch();
    }
}
