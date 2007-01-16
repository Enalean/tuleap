<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_View
*/
require_once(dirname(__FILE__).'/../Docman_VersionFactory.class.php');
require_once(dirname(__FILE__).'/../Docman_Icons.class.php');
require_once('Docman_View_GetMenuItemsVisitor.class.php');

/* abstract */ class Docman_View_View {
    var $dfltSortParams = null;
    var $dfltSearchParams = null;

    var $_controller;
    
    function Docman_View_View(&$controller) {
        $this->_controller = $controller;
    }
    
    function display($params = array()) {
        $GLOBALS['Language']->loadLanguageMsg('docman', 'docman');
        $this->_header($params);
        $this->_scripts($params);
        $this->_feedback($params);
        $this->_toolbar($params);
        $this->_title($params);
        $this->_breadCrumbs($params);
        $this->_mode($params);
        $this->_filter($params);
        $this->_content($params);
        $this->_footer($params);
    }
    /* protected */ function _header($params) {
    }
    /* protected */ function _scripts($params) {
    }
    /* protected */ function _feedback($params) {
    }
    /* protected */ function _toolbar($params) {
    }
    /* protected */ function _title($params) {
    }
    /* protected */ function _breadCrumbs($params) {
    }
    /* protected */ function _mode($params) {
    }
    /* protected */ function _filter($params) {
    }
    /* protected */ function _content($params) {
    }
    /* protected */ function _footer($params) {
    }
    
    
    function &_getVersionFactory($params) {
        $vf = new Docman_VersionFactory();
        return $vf;
    }
    function &_getDocmanIcons($params) {
        $icons = new Docman_Icons($params['theme_path'] .'/images/ic/');
        return $icons;
    }
    function &_getItemFactory($params) {
        $f = new Docman_ItemFactory();
        return $f;
    }
    /* static */ function buildUrl($prefix, $parameters, $amp = true) {
        $et = $amp ? '&amp;' : '&';
        $url = '';
        if ($prefix) {
            $url = $prefix;
        }
        if (count($parameters)) {
            if ($url) {
                $url .= $et;
            }
            $i = 0;
            $nb = count($parameters);
            foreach($parameters as $key => $value) {
                $i++;
                $url .= $key .'='. $value . ($i == $nb ? '' : $et);
            }
        }
        return $url;
    }

    /* static */ function buildPopupUrl($prefix, $parameters, $injs = false) {
        $url = Docman_View_View::buildUrl($prefix, $parameters, !$injs);
        
        $jsEscape[false] = '\'';
        $jsEscape[true]  = '\\\'';

        $escapedU = $jsEscape[$injs].$url.$jsEscape[$injs];

        $url = 'javascript:help_window('.$escapedU.')';


        return $url;
    }

    /* static */ function buildActionUrl($params, $parameters, $injs = false, $popup = false) {
        $output = '';

        $prefix = '';
        if(isset($params['default_url']) 
           && $params['default_url'] != false 
           && $params['default_url'] !== null) {
            $prefix = $params['default_url'];
        }

        if(isset($params['pv']) 
           && $params['pv'] !== false 
           && $popup) {
            $output = Docman_View_View::buildPopupUrl($prefix, $parameters, $injs);
        }
        else {
            if(isset($params['pv']) && $params['pv'] !== false) {
                $parameters['pv'] = $params['pv'];
            }
            if(isset($params['report']) && $params['report'] !== false) {
                $parameters['report'] = $params['report'];
            }
            $output = Docman_View_View::buildUrl($prefix, $parameters, !$injs);
        }

        return $output;
    }

    function _initSearchAndSortParams($params) {
        if($this->dfltSortParams === null) {
            $this->dfltSortParams = null;
            $this->dfltSearchParams = null;

            if(isset($params['filter']) && $params['filter'] !== null) {
                $fi =& $params['filter']->getFilterIterator();
                if($fi !== null) {
                    $fi->rewind();
                    while($fi->valid()) {
                        $f =& $fi->current();
                        
                        if($f !== null) {
                            $this->dfltSearchParams = array_merge($this->dfltSearchParams,
                                                                  $f->getUrlParameters());

                            $sort = $f->getSort();
                            if($sort !== null) {
                                $this->dfltSortParams[$f->getSortParam()] = $sort;
                            }
                        }

                        $fi->next();
                    }
                }
            }
        }
    }

    function getSearchParams($params) {
        $this->_initSearchAndSortParams($params);
        return $this->dfltSearchParams;
    }
    
    function getSortParams($params) {
        $this->_initSearchAndSortParams($params);
        return $this->dfltSortParams;
    }

    function isActionAllowed($action, &$item) {
        $if =& Docman_ItemFactory::instance($item->getGroupId());
        switch ($action) {
            case 'details&section=notifications':
            case 'details&section=history':
            case 'details':
                $allowed = $this->_controller->userCanRead($item->getId());
                break;
            case 'move':
                $allowed = $if->isMoveable($item) && $this->_controller->userCanWrite($item->getId()) && $this->_controller->userCanWrite($item->getParentId());
                break;
            case 'confirmDelete':
                $allowed = !$if->isRoot($item) && $this->_controller->userCanWrite($item->getId()) && $this->_controller->userCanWrite($item->getParentId());
                break;
            case 'action_update':
            case 'action_new_version':
            case 'newFolder':
            case 'newDocument':
                $allowed = $this->_controller->userCanWrite($item->getId());
                break;
            case 'details&section=permissions':
                $allowed = $this->_controller->userCanManage($item->getId());
                break;
            default:
                $allowed = false;
                break;
        }
        return $allowed;
    }
    function getItemMenu(&$item, $params, $bc = false) {
        
        $docman_icons =& $this->_getDocmanIcons($params);
        
        $html = '';
        $html .= '<span class="docman_item_options">';
        $html .= '<a title="'. $GLOBALS['Language']->getText('plugin_docman', 'tooltip_show_actions') .'" href="'. $params['default_url'] .'&amp;action=details&amp;id='. $item->getId() .'" id="docman_item_show_menu_'. $item->getId() .'">';
        $html .= '<img src="'. $docman_icons->getActionIcon('popup') .'" class="docman_item_icon" />';
        $html .= '</a>';
        $html .= '<script type="text/javascript">
        //<!--
        ';
        $user_actions = $item->accept(new Docman_View_GetMenuItemsVisitor());
        foreach($user_actions as $key => $nop) {
            if ($this->isActionAllowed($user_actions[$key]->action, $user_actions[$key]->item)) {
                $html .= $user_actions[$key]->fetchAsJavascript(array_merge($params, array('docman_icons' => &$docman_icons, 'bc' => $bc)));
            }
        }
        $html .= '
        //-->
        </script>';                
        $html .= '</span>';
        return $html;
    }
}

?>
