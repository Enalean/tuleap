<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('Docman_View_ItemDetailsSectionActions.class.php');
require_once('Docman_View_ParentsTree.class.php');

require_once(dirname(__FILE__).'/../Docman_ItemBo.class.php');
class Docman_View_ItemDetailsSectionMove extends Docman_View_ItemDetailsSectionActions {
    
    var $token;
    function Docman_View_ItemDetailsSectionMove(&$item, $url, &$controller, $params, $token) {
        parent::Docman_View_ItemDetailsSectionActions($item, $url, false, true, $controller);
        $this->params = $params;
        $this->token = $token;
    }
    function getContent() {
        $content = '';
        $content .= '<dl><dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_move') .'</dt><dd>';
        $content .= '<form action="'. $this->url .'" method="POST">';
        
        $parents_tree =& new Docman_View_ParentsTree($this->_controller);
        $content .= $parents_tree->fetch(array(
            'docman_icons' => $this->params['docman_icons'],
            'current'      => $this->item->getParentId(),
            'hierarchy'    => $this->params['hierarchy'],
            'input_name'   => 'id',
            'excludes'     => array($this->item->getId())
        ));
        $content .= '<script type="text/javascript">docman.options.move.item_id = '. $this->item->getId() .';</script>';
        $content .=  '<br />';
        
        //submit
        $content .= '<div>';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="'. $this->token .'" />';
        }
        $content .= '<input type="hidden" name="action" value="move_here" />';
        $content .= '<input type="hidden" name="item_to_move" value="'. $this->item->getId() .'" />';
        $content .= '<input type="submit" tabindex="2" name="confirm" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $content .= '<input type="submit" tabindex="1" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" />';
        $content .= '</div></form>';
        $content .= '</dd></dl>';
        return $content;
    }
    /* protected */ function _getJSDocmanParameters() {
        return array('action' => 'move');
    }
}
?>
