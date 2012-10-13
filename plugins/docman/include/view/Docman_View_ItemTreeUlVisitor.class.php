<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('Docman_View_GetActionOnIconVisitor.class.php');
require_once('Docman_View_GetClassForLinkVisitor.class.php');

class Docman_View_ItemTreeUlVisitor /* implements Visitor*/ {
    var $html;
    var $js;
    var $stripFirstNode;
    var $firstNodeStripped;
    var $docmanIcons;
    var $showOptions;
    var $defaultUrl;
    var $get_action_on_title;
    var $get_class_for_link;
    var $hp;

    function Docman_View_ItemTreeUlVisitor($view, $params = null) {
        $this->view                =& $view;
        $this->get_action_on_icon =& new Docman_View_GetActionOnIconVisitor();
        $this->get_class_for_link  =& new Docman_View_GetClassForLinkVisitor();
        $this->html                = '';
        $this->js                  = '';
        $this->stripFirstNode      = true;
        $this->firstNodeStripped   = false;
        $this->hp                  =& Codendi_HTMLPurifier::instance();
        $this->params              = $params;
        if (!isset($this->params['default_url'])) {
            $this->params['default_url'] = null;
        }
    }

    function toHtml() {
        return $this->html;
    }
  
    function getJavascript() {
        return $this->js;
    }
    
    function _canDisplayItem($item) {
        return true;
    }
    function _canDisplaySubItems($item) {
        return true;
    }
    
    function visitFolder(&$item, $params = array()) {
        $li_displayed = $this->_displayItem($item, $params);
        if($this->_canDisplaySubItems($item)) {
            $items =& $item->getAllItems();
            if ($items) {
                $nb = $items->size();
                if ($nb) { 
                    $this->html .= '<ul id="subitems_'.$item->getId().'" class="docman_items">'."\n";
                    $i = 0;
                    $iter =& $items->iterator();
                    $iter->rewind();
                    while($iter->valid()) {
                        $child =& $iter->current();
                        $child->accept($this, array('is_last' => (++$i == $nb)));
                        $iter->next();
                    }
                    
                    $this->html .= '</ul>'."\n";
                }
            }
        }
        
        if($li_displayed) {
            $this->html .= '</li>'."\n";
        }
    }
    function visitDocument(&$item, $params = array()) {
        $params['popup_doc'] = true;
        $li_displayed = $this->_displayItem($item, $params);
        if($li_displayed) {
            $this->html .= '</li>'."\n";
        }
    }
    function visitWiki(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function visitEmpty(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    //{{{
    function _displayItem(&$item, $params) {
        $li_displayed = false;
        if($this->stripFirstNode && !$this->firstNodeStripped) {
            $this->firstNodeStripped=true;
            if (isset($this->params['display_description']) && $this->params['display_description']) {
                $this->html .= '<p>'. $item->getDescription() .'</p>';
            }
        }
        else {
            if($item !== null && $this->_canDisplayItem($item)) {
                $this->html .= '<li id="item_'.$item->getId().'" class="'. Docman_View_Browse::getItemClasses($params) .'">';
                $params['expanded'] = true;
                $open = '_open';
                if(!isset($this->params['item_to_move']) && (user_get_preference(PLUGIN_DOCMAN_EXPAND_FOLDER_PREF.'_'.$item->getGroupId().'_'.$item->getId()) === false)) {
                    $params['expanded'] = false;
                    $open   = '';
                }
                $icon_src = $this->params['docman_icons']->getIconForItem($item, $params);
                $icon = '<img src="'. $icon_src .'" id="docman_item_icon_'.$item->getId().'" class="docman_item_icon" />';
                
                $this->html .= '<div>';
                $action = isset($this->params['item_to_move']) ? false : $item->accept($this->get_action_on_icon, array('view' => &$this->view));
                if ($action) {
                    $class = $item->accept($this->get_class_for_link, array('view' => &$this->view));
                    if ($class) {
                        $class .= $open;
                    }
                    $url = Docman_View_View::buildUrl($this->params['default_url'], array('action' => $action,
                                                                                          'id' => $item->getId()));
                    $this->html .= '<a href="'.$url.'" id="docman_item_link_'.$item->getId().'" class="'. $class .'">';
                }
                $this->html .=  $icon;

                //Display a lock icon for the locked document
                $dpm = Docman_PermissionsManager::instance($item->getGroupId());

                if ($action) {
                    $this->html .= '</a>';
                }
                $this->html .=  '<span class="docman_item_title">';
                if ($action) {
                    $url = Docman_View_View::buildActionUrl($this->params, 
                                                            array('action' => 'show',
                                                                  'id' => $item->getId()),
                                                            false,
                                                            isset($params['popup_doc']) ? true : false);
                    $this->html .= '<a href="'.$url.'" id="docman_item_title_link_'.$item->getId().'">';
                }
                
                $this->html .=   $this->hp->purify($item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML) ;
                if ($action) {
                    $this->html .= '</a>';
                }
                $this->html .=  '</span>';
                
                if($dpm->getLockFactory()->itemIsLocked($item)) {
                    $lockIconSrc = $this->params['docman_icons']->getIcon('lock_delete.png');
                    $lockIcon    = '<i id="docman_item_icon_locked_'.$item->getId().'"  title="'.$GLOBALS['Language']->getText('plugin_docman','event_lock_add').'" class="icon-lock"></i>';
                    $this->html .=  $lockIcon;
                }
                $this->html .= $this->view->getItemMenu($item, $this->params);
                $this->js .= $this->view->getActionForItem($item);
                $this->html .= '</div>';
                
                if (trim($item->getDescription()) != '') {
                    $this->html .= '<div class="docman_item_description">'. $this->hp->purify($item->getDescription(), CODENDI_PURIFIER_BASIC, $item->getGroupId()) .'</div>';
            }
                $li_displayed = true;
            }
        }
        return $li_displayed;
    }
    //}}}
}
?>
