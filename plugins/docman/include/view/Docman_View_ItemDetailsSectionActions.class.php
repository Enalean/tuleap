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
require_once('Docman_View_ItemDetailsSection.class.php');

class Docman_View_ItemDetailsSectionActions extends Docman_View_ItemDetailsSection {
    var $is_moveable;
    var $is_deleteable;
    var $_controller;
    function Docman_View_ItemDetailsSectionActions(&$item, $url, $is_moveable, $is_deleteable, &$controller) {
        $this->is_moveable   = $is_moveable;
        $this->is_deleteable = $is_deleteable;
        $this->_controller   = $controller;
        parent::Docman_View_ItemDetailsSection($item, $url, 'actions', $GLOBALS['Language']->getText('plugin_docman','details_actions'));
    }
    function getContent() {
        $folder_or_document = is_a($this->item, 'Docman_Folder') ? 'folder' : 'document';

        $content = '';
        
        $content .= '<dl>';
        
        //{{{ New Version
        $content .= $this->item->accept($this);
        //}}}
        
        //{{{ Move
        $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_move') .'</dt><dd>';
        if (!$this->is_moveable || !($this->_controller->userCanWrite($this->item->getId()) && $this->_controller->userCanWrite($this->item->getParentId()))) {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_move_cannotmove_'.$folder_or_document);
        } else {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 
                'details_actions_move_canmove_'.$folder_or_document, 
                Docman_View_View::buildUrl($this->url, array('action' => 'move', 'id' => $this->item->getId()))
            );
        }
        $content .= '</dd>';
        //}}}
        
        //{{{ Copy
        $content .= '<dt>'.Docman::txt('details_actions_copy').'</dt><dd>';
        $copyurl = Docman_View_View::buildUrl($this->url, array('action' => 'action_copy', 'id' => $this->item->getId(), 'orig_action' => 'details', 'orig_id' => $this->item->getId()));
        $content .= Docman::txt('details_actions_copy_cancopy_'.$folder_or_document, $copyurl);
        $content .= '</dd>';
        //}}}

        //{{{ Delete
        $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_delete') .'</dt><dd>';
        if (!$this->is_deleteable || !($this->_controller->userCanWrite($this->item->getid()) && $this->_controller->userCanWrite($this->item->getParentId()))) {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_delete_cannotdelete_'.$folder_or_document);
        } else {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 
                'details_actions_delete_candelete_'.$folder_or_document, 
                Docman_View_View::buildUrl($this->url, array('action' => 'confirmDelete', 'id' => $this->item->getId()))
            );
        }
        $content .= '</dd>';
        //}}}
        
        $content .= '</dl>';
        return $content;
    }
    
    function visitFolder(&$item, $params = array()) {
        $content = '';
        if ($this->_controller->userCanWrite($this->item->getid())) {
            $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newdocument') .'</dt><dd>';
            $content .= $GLOBALS['Language']->getText('plugin_docman', 
                'details_actions_newdocument_cancreate', 
                Docman_View_View::buildUrl($this->url, array('action' => 'newDocument', 'id' => $item->getId()))
            );
            $content .= '</dd>';
            $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newfolder') .'</dt><dd>';
            $content .= $GLOBALS['Language']->getText('plugin_docman', 
                'details_actions_newfolder_cancreate', 
                Docman_View_View::buildUrl($this->url, array('action' => 'newFolder', 'id' => $item->getId()))
            );
            //{{{ Paste
            $itemFactory =& Docman_ItemFactory::instance($item->getGroupId());
            $copiedItemId = $itemFactory->getCopyPreference($this->_controller->getUser());
            if($copiedItemId != false) {
                $copiedItem = $itemFactory->getItemFromDb($copiedItemId);
                $content .= '</dd>';
                $content .= '<dt>'.Docman::txt('details_actions_paste').'</dt><dd>';
                $copyurl = Docman_View_View::buildUrl($this->url, array('action' => 'action_paste', 'id' => $this->item->getId()));
                $content .= Docman::txt('details_actions_paste_canpaste', array($copyurl, $copiedItem->getTitle()));
            }
            //}}}
        }
        $content .= '</dd>';
        return $content;
    }
    function visitDocument(&$item, $params = array()) {
        $content = '';
        $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update') .'</dt><dd>';
        if (!$this->_controller->userCanWrite($this->item->getid())) {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_cannot');
        } else {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 
                'details_actions_update_can', 
                Docman_View_View::buildUrl($this->url, array('action' => 'action_update', 'id' => $this->item->getId()))
            );
        }
        /*$content .= '<form action="'. $this->url .'&amp;id='. $this->item->getId() .'" method="post">';
        
        require_once('Docman_View_GetSpecificFieldsVisitor.class.php');
        $fields = $item->accept(new Docman_View_GetSpecificFieldsVisitor(), array('request' => &$this->controller->request));
        $content .= '<table>';
        foreach($fields as $field) {
            $content .= '<tr style="vertical-align:top;"><td><label>'. $field['label'] .'</label></td><td>'. $field['field'] .'</td></tr>';
        }
        $content .= '</table>';
        $content .= '<input type="hidden" name="item[id]" value="'. $item->getId() .'" />';
        $content .= '<input type="hidden" name="action" value="update_wl" />';
        $content .= '<input type="submit" value="'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update') .'" />';
        
        $content .= '</form>';
        */
        $content .= '</dd>';
        return $content;
    }
    function visitWiki(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        $content = '';
        $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion') .'</dt><dd>';
        if (!$this->_controller->userCanWrite($this->item->getid())) {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion_cannotcreate');
        } else {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 
                'details_actions_newversion_cancreate', 
                Docman_View_View::buildUrl($this->url, array('action' => 'action_new_version', 'id' => $this->item->getId()))
            );
        }
        $content .= '</dd>';
        return $content;
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        $content = '<textarea name="content" rows="15" cols="50">';
        $version = $item->getCurrentVersion();
        if (is_file($version->getPath())) {
            $content .= file_get_contents($version->getPath());
        }
        $content .= '</textarea>';
        return $this->visitFile($item, array_merge($params, array('input_content' => $content)));
    }

    function visitEmpty(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
}
?>
