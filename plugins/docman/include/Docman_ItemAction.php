<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 * ItemAction is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_ItemAction
{
    public $item;
    public $action;
    public $class;
    public $title;
    public $other_icons;
    public $extraUrlParams;
    public function __construct(&$item)
    {
        $this->item = $item;
        $this->action = '';
        $this->classes = '';
        $this->title = '';
        $this->other_icons = [];
        $this->extraUrlParams = [];
    }
    public function fetchAction($params)
    {
        $url = $params['default_url'] . '&action=' . $this->action . '&id=' . $this->item->getId();
        $title = $this->title;
        $href = '<a href="' . $url . '">' . $title . '</a>';
        $html = '<li>' . $href . '</li>';
        return $html;
    }
    public function fetch($params)
    {
        $dfltUrlParams = ['action' => $this->action, 'id' => $this->item->getId()];
        $_urlParams = \array_merge($dfltUrlParams, $this->extraUrlParams);
        $url = \Tuleap\Docman\View\DocmanViewURLBuilder::buildActionUrl($this->item, $params, $_urlParams, \true, \true);
        $html = '<a href="' . $url . '" class="' . $this->classes . '" title="' . $this->title . '">';
        $html .= '<img src="' . $params['docman_icons']->getActionIcon($this->action) . '" class="docman_item_icon" alt="[' . $this->title . ']" />';
        $html .= '</a>&nbsp;';
        return $html;
    }
}
