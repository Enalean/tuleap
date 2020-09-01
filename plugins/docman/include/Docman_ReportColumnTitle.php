<?php
/**
 * Copyright (c) Enalean, 2015-Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_ReportColumnTitle extends \Docman_ReportColumn
{
    public function __construct($md)
    {
        parent::__construct($md);
    }
    public function getTableBox($item, $view, $params)
    {
        $html = '';
        $docmanIcons = $view->_getDocmanIcons($params);
        $icon_src = $docmanIcons->getIconForItem($item, $params);
        $icon = '<img src="' . $icon_src . '" class="docman_item_icon" />';
        $html .= '<span style="white-space: nowrap;">';
        $html .= $icon;
        $url = \Tuleap\Docman\View\DocmanViewURLBuilder::buildActionUrl($item, $params, ['action' => 'show', 'id' => $item->getId()], \false, \true);
        $html .= '<a href="' . $url . '" id="docman_item_title_link_' . $item->getId() . '">';
        $html .= \htmlentities($item->getTitle(), \ENT_QUOTES, 'UTF-8');
        $html .= '</a>';
        $html .= $view->getItemMenu($item, $params);
        $html .= '</span>';
        return $html;
    }
    public function getJavascript($item, $view)
    {
        return $view->getActionForItem($item);
    }
}
