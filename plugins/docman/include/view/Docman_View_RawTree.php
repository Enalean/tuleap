<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_RawTreeView
*/

class Docman_View_RawTree extends Docman_View_View //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    /* protected */ #[\Override]
    public function _content($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $itemFactory = new Docman_ItemFactory($params['group_id']);

        $itemTree = $itemFactory->getItemSubTree($params['item'], $params['user']);

        $displayItemTreeVisitor = new Docman_View_ItemTreeUlVisitor($this, [
            'theme_path'             => $params['theme_path'],
            'docman_icons'           => $this->_getDocmanIcons($params),
            'default_url'            => $params['default_url'],
            //'display_description'    => isset($params['display_description']) ? $params['display_description'] : true,
            'show_options'           => ($this->_controller->request->exist('show_options') ? $this->_controller->request->get('show_options') : false),
            'pv'                     => isset($params['pv']) ? $params['pv'] : false,
            'report'                 => isset($params['report']) ? $params['report'] : false,
            'item'                   => $params['item'],
        ]);
        $itemTree->accept($displayItemTreeVisitor);

        $this->javascript .= $displayItemTreeVisitor->getJavascript();

        echo $displayItemTreeVisitor->toHtml();
    }

    public function getClassForFolderLink()
    {
        return 'docman_item_type_folder';
    }

    #[\Override]
    public function _javascript($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // force docman object to watch click on pen icon
        parent::_javascript($params);
    }
}
