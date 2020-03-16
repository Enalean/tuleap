<?php
/**
 * Copyright (c) Enalean, 2019-Parent. All Rights Reserved.
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
use Tuleap\Docman\view\DocumentFooterPresenterBuilder;

/* abstract */ class Docman_View_Display extends Docman_View_Docman
{

    public function _title($params)
    {
        // No title in printer version
        if (isset($params['pv']) && $params['pv'] > 0) {
            return;
        }

        $renderer  = TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../../templates");
        $renderer->renderToPage(
            'docman-title',
            ["title" => $this->getUnconvertedTitle($params)]
        );
    }

    /* protected */ public function _footer($params)
    {
        $builder   = new DocumentFooterPresenterBuilder(ProjectManager::instance(), EventManager::instance());
        $presenter = $builder->build($params, $params['group_id'], $params['item']->toRow(), $params['user']);
        $renderer  = TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../../templates");
        $renderer->renderToPage(
            'docman-footer',
            $presenter
        );
        parent::_footer($params);
    }

    public function _breadCrumbs($params)
    {
        $hp                 = Codendi_HTMLPurifier::instance();
        $item               = $params['item'];
        $current_item       = $item;
        $current_item_title = $item->getTitle();
        $id                 = $item->getId();
        $parents            = array();
        $item_factory       = $this->_getItemFactory($params);
        while ($item->getParentId() != 0) {
            $item = $item_factory->getItemFromDb($item->getParentId());
            $parents[] = array(
                'item'  => $item,
                'id'    => $item->getId(),
                'title' => $item->getTitle()
            );
        }
        $urlAction = 'show';
        if (isset($params['action'])) {
            if ($params['action'] == 'search') {
                $urlAction = $params['action'];
            }
        }
        $this->_initSearchAndSortParams($params);
        $urlParams = array_merge(
            $this->dfltSortParams,
            $this->dfltSearchParams
        );
        $urlParams['action'] = $urlAction;
        $html = '';
        $html .= '<table border="0" width="100%">';
        $html .= '<tr>';
        $html .= '<td align="left">';
        $html .= '<div id="docman_item_title_link_' . $id . '">' . dgettext('tuleap-docman', 'Location:') . ' ';
        $parents = array_reverse($parents);
        foreach ($parents as $parent) {
            $urlParams['id'] = $parent['id'];
            $url             = DocmanViewURLBuilder::buildActionUrl($parent['item'], $params, $urlParams);
            $html           .= '&nbsp;<a href="' . $url . '">' .  $hp->purify($parent['title'], CODENDI_PURIFIER_CONVERT_HTML)  . '</a>&nbsp;/';
        }
        $urlParams['id'] = $id;
        $url = DocmanViewURLBuilder::buildActionUrl($params['item'], $params, $urlParams);
        $html .= '&nbsp;<a href="' . $url . '"><b>' .  $hp->purify($current_item_title, CODENDI_PURIFIER_CONVERT_HTML)  . '</b></a>';
        $html .= $this->getItemMenu($current_item, $params, $bc = true);
        $this->javascript .= $this->getActionForItem($current_item);
        $html .= '</div>';
        $html .= '</td>';

        echo $html;
    }

    public function _javascript($params)
    {
        // force docman object to watch click on pen icon
        $this->javascript .= "docman.initShowOptions();\n";
        parent::_javascript($params);
    }

    public function _mode($params)
    {
        $html = '';
         // Close table opened in method 'breadCrumbs'.
        $html .= '</tr>';
        $html .= '</table>';
        echo $html;
    }
}
