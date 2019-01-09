<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use Tuleap\Docman\DocumentTitlePresenter;
use Tuleap\Docman\ExternalLinks\ExternalLinksManager;

/* abstract */ class Docman_View_Display extends Docman_View_Docman
{

    function _title($params)
    {
        // No title in printer version
        if (isset($params['pv']) && $params['pv'] > 0) {
            return;
        }

        $folder_id               = 0;
        $item                    = $params['item']->toRow();
        $is_folder_migrated_view = $item['item_type'] === PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
            && isset($params['action']) && $params['action'] === "show";

        if ($is_folder_migrated_view && $item['parent_id']!== 0) {
            $folder_id =  $params['item']->getId();
        }
        $this->displayNewDocumentViewButton(
            $params['group_id'],
            $this->getUnconvertedTitle($params),
            $folder_id,
            $is_folder_migrated_view
        );
    }

    function _breadCrumbs($params) {
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
                'id'    => $item->getId(),
                'title' => $item->getTitle()
            );
        }
        $urlAction = 'show';
        if(isset($params['action'])) {
            if($params['action'] == 'search') {
                $urlAction = $params['action'];
            }
        }
        $this->_initSearchAndSortParams($params);
        $urlParams = array_merge($this->dfltSortParams, 
                                 $this->dfltSearchParams);
        $urlParams['action'] = $urlAction;
        $html = '';
        $html .= '<table border="0" width="100%">';
        $html .= '<tr>';
        $html .= '<td align="left">';
        $html .= '<div id="docman_item_title_link_'. $id .'">'. $GLOBALS['Language']->getText('plugin_docman', 'breadcrumbs_location') .' ';
        $parents = array_reverse($parents);
        foreach($parents as $parent) {
            $urlParams['id'] = $parent['id'];
            $url = $this->buildActionUrl($params, $urlParams);
            $html .= '&nbsp;<a href="'.$url.'">'.  $hp->purify($parent['title'], CODENDI_PURIFIER_CONVERT_HTML)  .'</a>&nbsp;/';
        }
        $urlParams['id'] = $id;
        $url = $this->buildActionUrl($params, $urlParams);
        $html .= '&nbsp;<a href="'.$url.'"><b>'.  $hp->purify($current_item_title, CODENDI_PURIFIER_CONVERT_HTML)  .'</b></a>';
        $html .= $this->getItemMenu($current_item, $params, $bc = true);
        $this->javascript .= $this->getActionForItem($current_item); 
        $html .= '</div>';
        $html .= '</td>';

        echo $html;
    }

    function _javascript($params) {
        // force docman object to watch click on pen icon
        $this->javascript .= "docman.initShowOptions();\n";
        parent::_javascript($params);
    }
    
    function  _mode($params) {
        $html = '';
         // Close table opened in method 'breadCrumbs'.
        $html .= '</tr>';
        $html .= '</table>';
        echo $html;
    }

    private function displayNewDocumentViewButton(int $project_id, string $title, int $folder_id, bool $is_folder_migrated_view)
    {
        $collector = new ExternalLinksManager($project_id, $folder_id);
        if ($is_folder_migrated_view === true) {
            EventManager::instance()->processEvent($collector);
        }

        $project = ProjectManager::instance()->getProject($project_id);

        $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../../templates");
        $renderer->renderToPage(
            'docman-title',
            new DocumentTitlePresenter($project, $title, $collector)
        );
    }
}
