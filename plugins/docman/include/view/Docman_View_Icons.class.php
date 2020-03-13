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

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Icons extends Docman_View_Browse
{

    /* protected */ public function _content($params)
    {
        $html = '';

        $itemFactory = new Docman_ItemFactory($params['group_id']);
        $itemTree = $itemFactory->getItemSubTree($params['item'], $params['user']);

        $items = $itemTree->getAllItems();
        $nb = $items->size();
        if ($nb) {
            $html .= '<table border="0" cellpadding="0" cellspacing="4" width="100%">';
            $folders   = array();
            $documents = array();
            $it = $items->iterator();
            while ($it->valid()) {
                $o = $it->current();
                $this->is_folder = false;
                $o->accept($this);
                if ($this->is_folder) {
                    $folders[] = $o;
                } else {
                    $documents[] = $o;
                }
                $it->next();
            }
            $nb_of_columns = 4;
            $width         = floor(100 / $nb_of_columns);
            $sort          = function ($a, $b) {
                return strnatcasecmp($a->getTitle(), $b->getTitle());
            };
            usort($folders, $sort);
            usort($documents, $sort);
            $cells = array_merge($folders, $documents);
            $rows = array_chunk($cells, $nb_of_columns);
            $item_parameters = array(
                'icon_width'            => '32',
                'theme_path'            => $params['theme_path'],
                'get_action_on_icon'    => new Docman_View_GetActionOnIconVisitor(),
                'docman_icons'           => $this->_getDocmanIcons($params),
                'default_url'            => $params['default_url'],
                //'display_description'    => isset($params['display_description']) ? $params['display_description'] : true,
                'show_options'           => ($this->_controller->request->exist('show_options') ? $this->_controller->request->get('show_options') : false),
                'item'                  => $params['item'],
            );
            foreach ($rows as $row) {
                $html .= '<tr style="vertical-align:top">';
                foreach ($row as $cell => $nop) {
                    $html .= '<td width="' . $width . '%">' . $this->_displayItem($row[$cell], $item_parameters) . '</td>';
                }
                $html .= '<td width="' . $width . '%">&nbsp;</td>';
                $html .= '</tr>';
            }
            $html .= '</table>' . "\n";
        }
        echo $html;
    }

    public function visitFolder(&$item, $params)
    {
        $this->is_folder = true;
    }
    public function visitDocument(&$item, $params)
    {
    }
    public function visitWiki(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    public function visitLink(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    public function visitFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    public function visitEmbeddedFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmpty(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function _displayItem(&$item, $params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '<div id="item_' . $item->getId() . '" class="' . Docman_View_Browse::getItemClasses($params) . '" style="position:relative;">';

        $show_options = isset($params['show_options']) && $params['show_options'] == $item->getId();

        $icon_src = $params['docman_icons']->getIconForItem($item, $params);
        $icon = '<img src="' . $icon_src . '" class="docman_item_icon" style="vertical-align:middle; text-decoration:none;" />';

        $icon_url = DocmanViewURLBuilder::buildActionUrl(
            $item,
            $params,
            [
                'action' => $item->accept($params['get_action_on_icon'], ['view' => $this]),
                'id'     => $item->getId()
            ]
        );
        $title_url = DocmanViewURLBuilder::buildActionUrl(
            $item,
            $params,
            ['action' => 'show', 'id' => $item->getId()]
        );
        $html .= '<div><a href="' . $icon_url . '">' . $icon . '</a>';
        $html .= '<span class="docman_item_title"><a href="' . $title_url . '" id="docman_item_title_link_' . $item->getId() . '">' .  $hp->purify($item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML)  . '</a></span>';
        $html .= '</a>';
        //Show/hide options {{{
        $html .= $this->getItemMenu($item, $params);
        $this->javascript .= $this->getActionForItem($item);
        //}}}
        if (trim($item->getDescription()) != '') {
            $html .= '<div class="docman_item_description">' .  $hp->purify($item->getDescription(), CODENDI_PURIFIER_BASIC) . '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }
}
