<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
        $hp = Codendi_HTMLPurifier::instance();
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
        $html = '';
        $html .= '<table border="0" width="100%">';
        $html .= '<tr>';
        $html .= '<td align="left">';
        $html .= '<div id="docman_item_title_link_'. $id .'">'. $GLOBALS['Language']->getText('plugin_docman', 'breadcrumbs_location') .' ';
        $parents = array_reverse($parents);
        foreach($parents as $parent) {
            $urlParams['id'] = $parent['id'];
            $url = $this->buildActionUrl($params, $urlParams,false,false,true);
            $html .= '&nbsp;<a href="'.$url.'">'.  $hp->purify($parent['title'], CODENDI_PURIFIER_CONVERT_HTML)  .'</a>&nbsp;/';
        }
        $urlParams['id'] = $id;
        $url = $this->buildActionUrl($params, $urlParams,false,false,true);
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

}

?>
