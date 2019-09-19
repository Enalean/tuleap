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

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_ItemDetailsSectionActions extends Docman_View_ItemDetailsSection
{
    var $is_moveable;
    var $is_deleteable;
    var $_controller;
    function __construct($item, $url, $is_moveable, $is_deleteable, $controller)
    {
        $this->is_moveable   = $is_moveable;
        $this->is_deleteable = $is_deleteable;
        $this->_controller   = $controller;
        parent::__construct($item, $url, 'actions', $GLOBALS['Language']->getText('plugin_docman', 'details_actions'));
    }
    function getContent($params = [])
    {
        $folder_or_document = is_a($this->item, 'Docman_Folder') ? 'folder' : 'document';
        $user               = $this->_controller->getUser();

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
            $content .= $GLOBALS['Language']->getText(
                'plugin_docman',
                'details_actions_move_canmove_'.$folder_or_document,
                DocmanViewURLBuilder::buildActionUrl(
                    $this->item,
                    ['default_url' => $this->url],
                    ['action' => 'move', 'id' => $this->item->getId()]
                )
            );
        }
        $content .= '</dd>';
        //}}}

        //{{{ Cut
        $content .= '<dt>'.$GLOBALS['Language']->getText('plugin_docman', 'details_actions_cut').'</dt><dd>';
        $itemFactory = Docman_ItemFactory::instance($this->item->getGroupId());
        if ($itemFactory->isRoot($this->item)) {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_cut_cannotcut_'.$folder_or_document);
        } else {
            $cuturl = DocmanViewURLBuilder::buildActionUrl(
                $this->item,
                ['default_url' => $this->url],
                ['action' => 'action_cut', 'id' => $this->item->getId(), 'orig_action' => 'details', 'orig_id' => $this->item->getId()]
            );
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_cut_cancut_'.$folder_or_document, $cuturl);
        }
        $content .= '</dd>';
        //}}}

        //{{{ Copy
        $content .= '<dt>'.$GLOBALS['Language']->getText('plugin_docman', 'details_actions_copy').'</dt><dd>';
        $copyurl  = DocmanViewURLBuilder::buildActionUrl(
            $this->item,
            ['default_url' => $this->url],
            ['action' => 'action_copy', 'id' => $this->item->getId(), 'orig_action' => 'details', 'orig_id' => $this->item->getId()]
        );
        $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_copy_cancopy_'.$folder_or_document, $copyurl);
        $content .= '</dd>';
        //}}}

        //{{{ Delete
        $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_delete') .'</dt><dd>';
        if (! $this->is_deleteable || $this->_controller->userCannotDelete($user, $this->item)) {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_delete_cannotdelete_'.$folder_or_document);
        } else {
            $content .= $GLOBALS['Language']->getText(
                'plugin_docman',
                'details_actions_delete_candelete_'.$folder_or_document,
                DocmanViewURLBuilder::buildActionUrl(
                    $this->item,
                    ['default_url' => $this->url],
                    ['action' => 'confirmDelete', 'id' => $this->item->getId()]
                )
            );
        }
        $content .= '</dd>';
        //}}}

        $content .= '</dl>';
        return $content;
    }

    function visitFolder($item, $params = array())
    {
        $content = '';
        if ($this->_controller->userCanWrite($this->item->getid())) {
            $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newdocument') .'</dt><dd>';
            $content .= $GLOBALS['Language']->getText(
                'plugin_docman',
                'details_actions_newdocument_cancreate',
                DocmanViewURLBuilder::buildActionUrl($item, ['default_url' => $this->url], ['action' => 'newDocument', 'id' => $item->getId()])
            );
            $content .= '</dd>';
            $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newfolder') .'</dt><dd>';
            $content .= $GLOBALS['Language']->getText(
                'plugin_docman',
                'details_actions_newfolder_cancreate',
                DocmanViewURLBuilder::buildActionUrl($item, ['default_url' => $this->url], ['action' => 'newFolder', 'id' => $item->getId()])
            );
            //{{{ Paste
            $itemFactory  = Docman_ItemFactory::instance($item->getGroupId());
            $copiedItemId = $itemFactory->getCopyPreference($this->_controller->getUser());
            $cutItemId    = $itemFactory->getCutPreference($this->_controller->getUser(), $item->getGroupId());
            $srcItem = null;
            if ($copiedItemId !== false && $cutItemId === false) {
                $srcItem = $itemFactory->getItemFromDb($copiedItemId);
            } elseif ($copiedItemId === false && $cutItemId !== false && $item->getId() != $cutItemId) {
                $srcItem = $itemFactory->getItemFromDb($cutItemId);
            }
            if ($srcItem && !$itemFactory->isInSubTree($this->item->getId(), $srcItem->getId())) {
                $content .= '</dd>';
                $content .= '<dt>'.$GLOBALS['Language']->getText('plugin_docman', 'details_actions_paste').'</dt><dd>';
                $copyurl = DocmanViewURLBuilder::buildActionUrl($item, ['default_url' => $this->url], ['action' => 'action_paste', 'id' => $this->item->getId()]);
                $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_paste_canpaste', array($copyurl,  $this->hp->purify($srcItem->getTitle(), CODEX_PURIFIER_CONVERT_HTML) ));
            }
            //}}}
        }
        $content .= '</dd>';
        return $content;
    }
    function visitDocument($item, $params = array())
    {
        $content = '';
        $content .= '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update') .'</dt><dd>';

        if (!$this->_controller->userCanWrite($this->item->getid())) {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_cannot');
        } else {
            $content .= $GLOBALS['Language']->getText(
                'plugin_docman',
                'details_actions_update_can',
                DocmanViewURLBuilder::buildActionUrl($item, ['default_url' => $this->url], ['action' => 'action_update', 'id' => $this->item->getId()])
            );
        }

        $content .= '</dd>';
        return $content;
    }
    function visitWiki($item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    function visitLink($item, $params = array())
    {
        return $this->getSectionForNewVersion();
    }
    function visitFile($item, $params = array())
    {
        return $this->getSectionForNewVersion();
    }

    private function getSectionForNewVersion()
    {
        $content = '<dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion') .'</dt><dd>';
        if (!$this->_controller->userCanWrite($this->item->getid())) {
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_newversion_cannotcreate');
        } else {
            $content .= $GLOBALS['Language']->getText(
                'plugin_docman',
                'details_actions_newversion_cancreate',
                DocmanViewURLBuilder::buildActionUrl($this->item, ['default_url' => $this->url], ['action' => 'action_new_version', 'id' => $this->item->getId()])
            );
        }
        $content .= '</dd>';

        return $content;
    }

    function visitEmbeddedFile($item, $params = array())
    {
        $content = '<textarea name="content" rows="15" cols="50">';
        $version = $item->getCurrentVersion();
        if (is_file($version->getPath())) {
            $content .= file_get_contents($version->getPath());
        }
        $content .= '</textarea>';
        return $this->visitFile($item, array_merge($params, array('input_content' => $content)));
    }

    function visitEmpty($item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
}
