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
 * $Id$
 */
require_once('Docman_View_ItemDetailsSectionActions.class.php');

class Docman_View_ItemDetailsSectionUpdate extends Docman_View_ItemDetailsSectionActions {
    var $validate;
    var $force;
    function Docman_View_ItemDetailsSectionUpdate(&$item, $url, &$controller, $force) {
        parent::Docman_View_ItemDetailsSectionActions($item, $url, false, true, $controller);
        $this->force = $force;
    }
    function getContent() {
        return $this->item->accept($this);
    }
    
    function visitFolder(&$item, $params = array()) {
        return "";
    }
    function visitDocument(&$item, $params = array()) {
        $content = '';
        $content .= '<dl><dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update') .'</dt><dd>';
        $content .= '<form action="'. $this->url .'&amp;id='. $this->item->getId() .'" method="post">';
        
        require_once('Docman_View_GetSpecificFieldsVisitor.class.php');
        $fields = $item->accept(new Docman_View_GetSpecificFieldsVisitor(), array('force_item' => $this->force));
        $content .= '<table>';
        foreach($fields as $field) {
            $content .= '<tr style="vertical-align:top;"><td><label>'. $field->getLabel() .'</label></td><td>'. $field->getField() .'</td></tr>';
        }
        $content .= '</table>';
        $content .= '<input type="hidden" name="item[id]" value="'. $item->getId() .'" />';
        $content .= '<input type="hidden" name="action" value="update_wl" />';
        $content .= '<input type="submit" name="confirm" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $content .= '<input type="submit" name="cancel"  value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" />';
        
        $content .= '</form>';
        
        $content .= '</dd></dl>';
        return $content;
    }
    function visitWiki(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        return '';
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitFile($item, $params);
    }
    
}
?>
