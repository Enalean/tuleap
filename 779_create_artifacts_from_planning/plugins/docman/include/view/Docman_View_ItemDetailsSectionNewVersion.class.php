<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
require_once('Docman_View_ItemDetailsSectionActions.class.php');
require_once('Docman_View_ItemDetailsSectionApprovalCreate.class.php');
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

    function _getApprovalTable() {
        $html = '';

        $atf =& Docman_ApprovalTableFactory::getFromItem($this->item);
        if($atf->tableExistsForItem()) {
            $html .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_apptable') .'</dt><dd>';
            $html .= '<dd>';
            $html .= Docman_View_ItemDetailsSectionApprovalCreate::displayImportLastTable(false);
            $html .= '</dd>';
        }

        return $html;
    }

    function _getReleaseLock() {
        $content = '';
        $dPm = Docman_PermissionsManager::instance($this->item->getGroupId());
        if($dPm->getLockFactory()->itemIsLocked($this->item)) {
            $content .= '<tr style="vertical-align:top;">';
            $content .= '<td><label>'.$GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_lock').'</label></td>';
            $content .= '<td><input type="checkbox" name="lock_document" value="lock" /></td>';
            $content .= '</tr>';
        }
        return $content;
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
        $label = '';
        if (isset($this->_controller->_viewParams['label'])) {
            $label = $this->_controller->_viewParams['label'];
        }
        $changelog = '';
        if (isset($this->_controller->_viewParams['changelog'])) {
            $changelog = $this->_controller->_viewParams['changelog'];
        }

        $content = '';
        $content .= '<form action="'. $this->url .'&amp;id='. $this->item->getId() .'" method="post" enctype="multipart/form-data" id= "plugin_docman_new_version_form">';

        $content .= '<dl>';
        $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update') .'</dt><dd>';
        $content .= '<table>';
        $content .= '<tr style="vertical-align:top"><td>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion_label') .'</td><td><input type="text" name="version[label]" value="'.$label.'" /></td></tr>';
        $content .= '<tr style="vertical-align:top"><td>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion_changelog') .'</td><td><textarea name="version[changelog]" rows="7" cols="80">'.$changelog.'</textarea></td></tr>';
        $fields = $item->accept(new Docman_View_GetSpecificFieldsVisitor(), array('force_item' => $this->force, 'request' => &$this->controller->request));
        foreach($fields as $field) {
            $content .= '<tr style="vertical-align:top;">';
            $content .= '<td><label>'. $field->getLabel().'</label></td>';
            $content .= '<td>'. $field->getField() .'</td></tr>';
        }
        // Release lock
        $content .= $this->_getReleaseLock();

        $content .= '</table>';
        $content .= '</dd>';

        $content .= $this->_getApprovalTable();

        $content .= '<p>';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="'. $this->token .'" />';
        }
        $content .= '<input type="hidden" name="action" value="new_version" />';
        $content .= '<input type="submit" name="confirm" value="'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion_button').'" />';
        $content .= '<input type="submit" name="cancel"  value="'. $GLOBALS['Language']->getText('global', 'btn_cancel').'" />';
        $content .= '</p>';

        $content .= '</dl>';
        $content .= '</form>';
        $snippet = '
        document.observe("dom:loaded", function () {
            $("plugin_docman_new_version_form").observe("submit", function (e) {
                if (!docman.approvalTableCheck($("plugin_docman_new_version_form"))) {
                    e.stop();
                }
            });
        });
        ';
        $GLOBALS['Response']->includeFooterJavascriptSnippet($snippet);
        return $content;
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitFile($item, $params);
    }

    function visitEmpty(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
}
?>
