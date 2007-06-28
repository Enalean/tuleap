<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_Display
*/

require_once('Docman_View_Docman.class.php');

/* abstract */ class Docman_View_Display extends Docman_View_Docman {
    
    function _title($params) {
        // No title in printer version
        if(isset($params['pv']) && $params['pv'] > 0) {
            return;
        }
        echo '<h2>'. $this->_getTitle($params) .'</h2>';
    }
    function _breadCrumbs($params) {
        $item =& $params['item'];
        $current_item       =& $item;
        $current_item_title = $item->getTitle();
        $id                 = $item->getId();
        $parents = array();
        $item_factory =& $this->_getItemFactory($params);
        while ($item->getParentId() != 0) {
            $item =& $item_factory->getItemFromDb($item->getParentId());
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
        echo '<div style="position:relative;" id="item_'. $id .'">'. $GLOBALS['Language']->getText('plugin_docman', 'breadcrumbs_location') .' ';
        $parents = array_reverse($parents);
        foreach($parents as $parent) {
            $urlParams['id'] = $parent['id'];
            $url = $this->buildActionUrl($params, $urlParams);
            echo '&nbsp;<a href="'.$url.'">'. $parent['title'] .'</a>&nbsp;/';
        }
        $urlParams['id'] = $id;
        $url = $this->buildActionUrl($params, $urlParams);
        echo '&nbsp;<a href="'.$url.'"><b>'. $current_item_title .'</b></a>';
        
        echo $this->getItemMenu($current_item, $params, $bc = true);
        echo '</div>';
    }
}

?>
