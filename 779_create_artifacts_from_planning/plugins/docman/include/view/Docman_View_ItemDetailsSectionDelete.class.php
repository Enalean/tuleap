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

class Docman_View_ItemDetailsSectionDelete extends Docman_View_ItemDetailsSectionActions {
    
    var $token;
    function Docman_View_ItemDetailsSectionDelete(&$item, $url, &$controller, $token) {
        parent::Docman_View_ItemDetailsSectionActions($item, $url, false, true, $controller);
        $this->token = $token;
    }
    function getContent() {
        $folder_or_document = is_a($this->item, 'Docman_Folder') ? 'folder' : (is_a($this->item, 'Docman_File') ? 'file' : 'document');
        $item_type = $this->_controller->_getItemFactory()->getItemTypeForItem($this->item);
        
        $vVersion = new Valid_UInt('version');
        $vVersion->required();
        if ($this->_controller->request->valid($vVersion)) {
            $version = $this->_controller->request->get('version');
            $label = $this->_controller->request->get('label');
        } else {
            $version = false;
        }
        $content = '';
        $content .= '<dl><dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_delete') .'</dt><dd>';
        $content .= '<form action="'. $this->url .'" method="POST">';
        $content .= '<div class="docman_confirm_delete">';
        if ($version !== false) {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_delete_warning_version',  array($this->hp->purify($this->item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML), $version) );
        } else {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_delete_warning_'.$folder_or_document,  $this->hp->purify($this->item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML) );
        }
        if($item_type == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
            $content .= $this->getWikiDeleteInfo();
        }
        $content .= '<div class="docman_confirm_delete_buttons">';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="'. $this->token .'" />';
        }
        $content .= '     <input type="hidden" name="section" value="actions" />';
        
        if ($version !== false) {
            $content .= '     <input type="hidden" name="action" value="deleteVersion" />';
            $content .= '     <input type="hidden" name="version" value="'.$version.'" />';
        } else {
            $content .= '     <input type="hidden" name="action" value="delete" />';
        }
        $content .= '     <input type="hidden" name="id" value="'. $this->item->getId() .'" />';
        $content .= '     <input type="submit" tabindex="2" name="confirm" value="'. $GLOBALS['Language']->getText('plugin_docman', 'details_delete_confirm') .'" />';
        $content .= '     <input type="submit" tabindex="1" name="cancel" value="'. $GLOBALS['Language']->getText('plugin_docman', 'details_delete_cancel') .'" />';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</form>';
        $content .= '</dd></dl>';
        return $content;
    }

    function getWikiDeleteInfo() {
        $output = '';
        $output .= $GLOBALS['Language']->getText('plugin_docman', 'details_delete_warning_wiki');

        // List of other possible referencers.
        $pagename = $this->item->getPagename();
        $referencers = $this->_controller->_getItemFactory()->getWikiPageReferencers($pagename, $this->item->getGroupId());
        if(is_array($referencers) && count($referencers) > 1) {
            $output .= $GLOBALS['Language']->getText('plugin_docman', 'details_delete_wiki_impact_on_documents');
            $output .= '<div id="other_referencers">';
            foreach($referencers as $key => $doc) {
                if($this->item->getId() != $doc->getId()) {
                    $output .= $this->getWikiDocumentPath($doc);
                }
            }
            $output .= '</div>';
        }

        $output .= '<p><input type="checkbox" id="cascadeWikiPageDeletion" name="cascadeWikiPageDeletion"/>';
        $output .= '<label for="cascadeWikiPageDeletion">';
        $output .= $GLOBALS['Language']->getText('plugin_docman', 'docman_wiki_delete_cascade');
        $output .= '</label></p>';
        
        return $output;
    }

    function getWikiDocumentPath($item) {
        $parents = array();
        $html = '';
        $hp = Codendi_HTMLPurifier::instance();

        $reference = $item;

        while ($item && $item->getParentId() != 0) {
            $item = $this->_controller->_getItemFactory()->getItemFromDb($item->getParentId());
            $parents[] = array(
                'id'    => $item->getId(),
                'title' => $item->getTitle()
            );
        }
        $parents = array_reverse($parents);
        $item_url = '/plugins/docman/?group_id=' . $item->getGroupId() . '&sort_update_date=0&action=show&id=';
        foreach($parents as $parent) {
            $html .= '<a href="'. $item_url. $parent['id']. '">'. $parent['title']. '</a>';
            $html .= ' / ';
        }

        $md_uri = '/plugins/docman/?group_id=' . $item->getGroupId() . '&action=details&id=' . $item->getId();

        //Add a pen icon linked to document properties.
        $pen_icon = '<a href="'. $md_uri . '"><img src="' . util_get_image_theme("ic/edit.png") . '" /></a>';

        $html .= '<a href="'. $item_url . $reference->getId() . '">'. $reference->getTitle() . '</a>';
        $html .= $pen_icon;
        $html .= '<br>';

        return $html;
    }
}
?>
