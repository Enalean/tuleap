<?php
/**
 * Copyright © Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Docman_View_ItemDetailsSectionDelete extends Docman_View_ItemDetailsSectionActions
{

    public $token;
    public function __construct($item, $url, $controller, $token)
    {
        parent::__construct($item, $url, false, true, $controller);
        $this->token = $token;
    }
    public function getContent($params = [])
    {
        $folder_or_document = is_a($this->item, 'Docman_Folder') ? 'folder' : (is_a($this->item, 'Docman_File') ? 'file' : 'document');
        $item_type = $this->_controller->getItemFactory()->getItemTypeForItem($this->item);

        $vVersion = new Valid_UInt('version');
        $vVersion->required();
        if ($this->_controller->request->valid($vVersion)) {
            $version = $this->_controller->request->get('version');
            $label = $this->_controller->request->get('label');
        } else {
            $version = false;
        }
        $content = '';
        $content .= '<dl><dt>' . dgettext('tuleap-docman', 'Delete') . '</dt><dd>';
        $content .= '<form action="' . $this->url . '" method="POST">';
        $content .= '<div class="docman_confirm_delete">';
        if ($version !== false) {
            $content .= sprintf(dgettext('tuleap-docman', '<h3>Confirm deletion of version %2$s of document %1$s</h3><p>You are going to delete a version of file. </p><p>Are you sure that you want to delete this version?</p>'), $this->hp->purify($this->item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML), $version);
        } elseif (is_a($this->item, 'Docman_Folder')) {
            $content .= sprintf(dgettext('tuleap-docman', '<h3>Confirm deletion of folder %1$s</h3><p>You are going to delete a folder. Please note that all sub-items and their versions will be deleted.</p><p>Are you sure that you want to delete this folder?</p>'), $this->hp->purify($this->item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML));
        } elseif (is_a($this->item, 'Docman_File')) {
            $content .= sprintf(dgettext('tuleap-docman', '<h3>Confirm deletion of document %1$s</h3><p>You are going to delete a file. Please note that all versions will be deleted.</p><p>Are you sure that you want to delete this file?</p>'), $this->hp->purify($this->item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML));
        } else {
            $content .= sprintf(dgettext('tuleap-docman', '<h3>Confirm deletion of document %1$s</h3><p>You are going to delete a document. </p><p>Are you sure that you want to delete this document?</p>'), $this->hp->purify($this->item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML));
        }
        if ($item_type == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
            $content .= $this->getWikiDeleteInfo();
        }
        $content .= '<div class="docman_confirm_delete_buttons">';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="' . $this->token . '" />';
        }
        $content .= '     <input type="hidden" name="section" value="actions" />';

        if ($version !== false) {
            $content .= '     <input type="hidden" name="action" value="deleteVersion" />';
            $content .= '     <input type="hidden" name="version" value="' . $version . '" />';
        } else {
            $content .= '     <input type="hidden" name="action" value="delete" />';
        }
        $content .= '     <input type="hidden" name="id" value="' . $this->item->getId() . '" />';
        $content .= '     <input type="submit" tabindex="2" name="confirm" value="' . dgettext('tuleap-docman', 'Yes, I am sure!') . '" />';
        $content .= '     <input type="submit" tabindex="1" name="cancel" value="' . dgettext('tuleap-docman', 'No, I do not want to delete it') . '" />';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</form>';
        $content .= '</dd></dl>';
        return $content;
    }

    public function getWikiDeleteInfo()
    {
        $output = '';
        $output .= dgettext('tuleap-docman', '<p><em>Please Note that if you check the \'Cascade deletion to wiki\' option, the referenced wiki page will no longer exist in wiki service too.</em></p>');

        // List of other possible referencers.
        $pagename = $this->item->getPagename();
        $referencers = $this->_controller->getItemFactory()->getWikiPageReferencers($pagename, $this->item->getGroupId());
        if (is_array($referencers) && count($referencers) > 1) {
            $output .= dgettext('tuleap-docman', '<p><em>You should also be aware that the following documents will no longer be valid if you choose to cascade deletion to wiki service:</em></p>');
            $output .= '<div id="other_referencers">';
            foreach ($referencers as $key => $doc) {
                if ($this->item->getId() != $doc->getId()) {
                    $output .= $this->getWikiDocumentPath($doc);
                }
            }
            $output .= '</div>';
        }

        $output .= '<p><input type="checkbox" id="cascadeWikiPageDeletion" name="cascadeWikiPageDeletion"/>';
        $output .= '<label for="cascadeWikiPageDeletion">';
        $output .= dgettext('tuleap-docman', 'Cascade deletion to wiki service');
        $output .= '</label></p>';

        return $output;
    }

    private function getWikiDocumentPath($item)
    {
        $parents = array();
        $html = '';

        $purifier = Codendi_HTMLPurifier::instance();

        $reference = $item;

        while ($item && $item->getParentId() != 0) {
            $item = $this->_controller->getItemFactory()->getItemFromDb($item->getParentId());
            $parents[] = array(
                'id'    => $item->getId(),
                'title' => $item->getTitle()
            );
        }
        $parents = array_reverse($parents);
        $item_url = '/plugins/docman/?group_id=' . urlencode($item->getGroupId()) . '&sort_update_date=0&action=show&id=';
        foreach ($parents as $parent) {
            $html .= '<a href="' . $item_url . urlencode($parent['id']) . '">' . $purifier->purify($parent['title']) . '</a>';
            $html .= ' / ';
        }

        $md_uri = '/plugins/docman/?group_id=' . urlencode($item->getGroupId()) . '&action=details&id=' . urlencode($item->getId());

        //Add a pen icon linked to document properties.
        $pen_icon = '<a href="' . $md_uri . '"><img src="' . util_get_image_theme("ic/edit.png") . '" /></a>';

        $html .= '<a href="' . $item_url . $reference->getId() . '">' . $purifier->purify($reference->getTitle()) . '</a>';
        $html .= $pen_icon;
        $html .= '<br>';

        return $html;
    }
}
