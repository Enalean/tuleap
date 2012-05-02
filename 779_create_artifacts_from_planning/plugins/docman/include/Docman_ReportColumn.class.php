<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

class Docman_ReportColumn {
    var $md;
    var $sort;

    function Docman_ReportColumn($md) {
        $this->md = $md;
        $this->sort = null;
    }

    function setSort($s) {
        $this->sort = $s;
    }
    function getSort() {
        return $this->sort;
    }

    function getSortParameter() {
        $sortParam = null;
        if($this->md !== null) {
            $sortParam = 'sort_'.$this->md->getLabel();
        }
        return $sortParam;
    }

    function getSortSelectorHtml() {
        $html = '';
        $sort = $this->getSort();
        if($sort !== null) {
            $html .= '<input type="hidden" name="'.$this->getSortParameter().'" value="'.$sort.'" />';
            $html .= "\n";
        }
        return $html;
    }
    

    function getTitle($view, $viewParams) {
        $sort = $this->getSort();
        if($sort == 1) {
            $toggleValue = '0';
            $toogleIcon = '<img src="'.util_get_image_theme("up_arrow.png").'" border="0" >';
        }
        else {
            $toggleValue = '1';
            $toogleIcon = '<img src="'.util_get_image_theme("dn_arrow.png").'" border="0" >';
        }
        
        //
        // URL
        //
        $toggleParam = array();
        $sortParam = $this->getSortParameter();
        if($sortParam !== null) {
            $toggleParam[$sortParam] = $toggleValue;
        }
        
        $url = $view->_buildSearchUrl($viewParams, array($sortParam => $toggleValue));
        $title = $GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_toggletitle');
        
        $link = $this->md->getName();
        if($sort !== null) {
            $link .= '&nbsp;'.$toogleIcon;
        }
        
        $href = '<a href="'.$url.'" title="'.$title.'">'.$link.'</a>';
        
        return $href;
    }

    function initFromRequest($request) {
        $sortparam = $this->getSortParameter();
        if($request->exist($sortparam)) {
            $this->setSort((int) $request->get($sortparam));
        }
    }

    function _getMdHtml($item) {
        $mdHtml = null;
        $md = $item->getMetadataFromLabel($this->md->getLabel());
        if($md !== null) {
            $mdHtml = Docman_MetadataHtmlFactory::getFromMetadata($md, array());
        }
        return $mdHtml;
    }

    function getTableBox($item, $view, $params) {
        $mdHtml = $this->_getMdHtml($item);
        if($mdHtml !== null) {
            return $mdHtml->getValue();
        }
        return '';
    }
    
    function getJavascript($item, $view) {
        return '';
    }
}

class Docman_ReportColumnLocation 
extends Docman_ReportColumn {
    function Docman_ReportColumnLocation() {
        $this->sort = null;
    }

    function setSort($s) {
        return;
    }

    function getSortSelectorHtml() {
        return;
    }

    function getTitle($defaultUrl) {
        return $GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_location');
    }

    function initFromRequest() {
        return;
    }

    function getTableBox($item, $view, $params) {
        $hp = Codendi_HTMLPurifier::instance();
        $pathTitle = $item->getPathTitle();
        $pathId    = $item->getPathId();
        $pathUrl   = array();
        foreach($pathTitle as $key => $title) {
            $id  = $pathId[$key];
            
            // Replace in the current url the id of the root item.
            $dfltParams = $view->_getDefaultUrlParams($params);
            $dfltParams['id'] = $id;
            $url = $view->buildActionUrl($params, $dfltParams);
            
            $href = '<a href="'.$url.'">'. $hp->purify($title, CODENDI_PURIFIER_CONVERT_HTML) .'</a>';
            $pathUrl[] = $href;
        }
        $html = implode(' / ', $pathUrl);
        return $html;
    }
}

class Docman_ReportColumnTitle
extends Docman_ReportColumn {
    function Docman_ReportColumnTitle($md) {
        parent::Docman_ReportColumn($md);
    }

    function getTableBox($item, $view, $params) {
        $html = '';
        $docmanIcons = $view->_getDocmanIcons($params);
        $icon_src = $docmanIcons->getIconForItem($item, $params);
        $icon = '<img src="'. $icon_src .'" class="docman_item_icon" />';
        $html .= '<span style="white-space: nowrap;">';
        $html .= $icon;
        $url = $view->buildActionUrl($params, 
                                     array('action' => 'show',
                                           'id' => $item->getId()), 
                                     false,
                                     true);
        $html .= '<a href="'.$url.'" id="docman_item_title_link_'.$item->getId().'">';
        $html .=  htmlentities($item->getTitle(), ENT_QUOTES, 'UTF-8');
        $html .=  '</a>';
        $html .= $view->getItemMenu($item, $params);
        $html .= '</span>';
        return $html;
    }
    
    function getJavascript($item, $view) {
        return $view->getActionForItem($item);
    }
}

class Docman_ReportColumnList
extends Docman_ReportColumn {
    function Docman_ReportColumnList($md) {
        parent::Docman_ReportColumn($md);
    }

    function getTableBox($item, $view, $params) {
        $mdHtml = $this->_getMdHtml($item);
        if($mdHtml !== null) {
            return $mdHtml->getValue(true);
        }
        return '';
    }
}
?>
