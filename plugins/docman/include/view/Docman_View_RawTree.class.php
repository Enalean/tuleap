<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_RawTreeView
*/

require_once('Docman_View_View.class.php');
require_once('Docman_View_ItemTreeUlVisitor.class.php');

require_once(dirname(__FILE__) . '/../Docman_ItemFactory.class.php');

class Docman_View_RawTree extends Docman_View_View
{

    /* protected */ public function _content($params)
    {
        $itemFactory = new Docman_ItemFactory($params['group_id']);

        $itemTree = $itemFactory->getItemSubTree($params['item'], $params['user']);

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

        $this->javascript .= $displayItemTreeVisitor->getJavascript();

        echo $displayItemTreeVisitor->toHtml();
    }
    public function getActionOnIconForFolder(&$folder, $force_collapse = true)
    {
        return $force_collapse || !(user_get_preference(PLUGIN_DOCMAN_EXPAND_FOLDER_PREF . '_' . $folder->getGroupId() . '_' . $folder->getId()) === false) ? 'collapseFolder' : 'expandFolder';
    }
    public function getClassForFolderLink()
    {
        return 'docman_item_type_folder';
    }

    public function _javascript($params)
    {
        // force docman object to watch click on pen icon
        $this->javascript .= "docman.initShowOptions();\n";
        parent::_javascript($params);
    }
}
