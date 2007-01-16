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
require_once('Docman_View_GetSpecificFieldsVisitor.class.php');

class Docman_View_ItemDetailsSectionNewVersion extends Docman_View_ItemDetailsSectionActions {
    
    var $force;
    var $token;
    function Docman_View_ItemDetailsSectionNewVersion(&$item, $url, &$controller, $force, $token) {
        parent::Docman_View_ItemDetailsSectionActions($item, $url, false, true, $controller);
        $this->force    = $force;
        $this->token = $token;
    }
    function getContent() {
        return $this->item->accept($this);
    }
    
    function visitFolder(&$item, $params = array()) {
        return "";
    }
    function visitDocument(&$item, $params = array()) {
        return "";
    }
    function visitWiki(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        $content = '';
        $content .= '<dl><dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update') .'</dt><dd>';
        $content .= '<form action="'. $this->url .'&amp;id='. $this->item->getId() .'" method="post" enctype="multipart/form-data">';
        
        $content .= '<table>';
        $content .= '<tr style="vertical-align:top"><td>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion_label') .'</td><td><input type="text" name="version[label]" value="" /></td></tr>';
        $content .= '<tr style="vertical-align:top"><td>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion_changelog') .'</td><td><textarea name="version[changelog]" rows="7" cols="80"></textarea></td></tr>';
        $fields = $item->accept(new Docman_View_GetSpecificFieldsVisitor(), array('force_item' => $this->force));
        foreach($fields as $field) {
            $content .= '<tr style="vertical-align:top;">';
            $content .= '<td><label>'. $field->getLabel().'</label></td>';
            $content .= '<td>'. $field->getField() .'</td></tr>';
        }
        $content .= '<tr style="vertical-align:top"><td></td><td>';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="'. $this->token .'" />';
        }
        $content .= '<input type="hidden" name="action" value="new_version" />';
        $content .= '<input type="submit" name="confirm" value="'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion_button').'" />';
        $content .= '<input type="submit" name="cancel"  value="'. $GLOBALS['Language']->getText('global', 'btn_cancel').'" /></td></tr>';
        $content .= '</table>';
        
        $content .= '</form>';
        $content .= '</dd></dl>';
        return $content;
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitFile($item, $params);
    }
}
?>
