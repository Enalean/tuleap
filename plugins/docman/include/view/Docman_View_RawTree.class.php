<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_RawTreeView
*/

require_once('Docman_View_View.class.php');
require_once('Docman_View_ItemTreeUlVisitor.class.php');

require_once(dirname(__FILE__).'/../Docman_ItemFactory.class.php');

class Docman_View_RawTree extends Docman_View_View {
    
    /* protected */ function _content($params) {
        
        $itemFactory = new Docman_ItemFactory($params['group_id']);

        $itemTree =& $itemFactory->getItemSubTree($params['item']->getId(), array(
            'user' => $params['user'], 
            'ignore_obsolete' => true,
        ));

        $displayItemTreeVisitor = new Docman_View_ItemTreeUlVisitor($this, array(
            'theme_path'             => $params['theme_path'],
            'docman_icons'           => $this->_getDocmanIcons($params),
            'default_url'            => $params['default_url'],
            //'display_description'    => isset($params['display_description']) ? $params['display_description'] : true,
            'show_options'           => ($this->_controller->request->exist('show_options') ? $this->_controller->request->get('show_options') : false),
            'pv'                     => isset($params['pv']) ? $params['pv'] : false,
            'report'                 => isset($params['report']) ? $params['report'] : false,
            'item'                   => $params['item'],
        ));
        $itemTree->accept($displayItemTreeVisitor);
        
        echo $displayItemTreeVisitor->toHtml();    
    }
    function getActionOnIconForFolder(&$folder, $force_collapse = true) {
        return $force_collapse || !(user_get_preference(PLUGIN_DOCMAN_EXPAND_FOLDER_PREF.'_'.$folder->getGroupId().'_'.$folder->getId()) === false) ? 'collapseFolder' : 'expandFolder';
    }
    function getClassForFolderLink() {
        return 'docman_item_type_folder';
    }
}

?>
