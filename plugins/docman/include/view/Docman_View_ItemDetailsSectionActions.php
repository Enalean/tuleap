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

use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\View\DocmanViewURLBuilder;

/**
 * @template-implements ItemVisitor<string>
 */
class Docman_View_ItemDetailsSectionActions extends Docman_View_ItemDetailsSection implements ItemVisitor
{
    public $is_moveable;
    public $is_deleteable;
    public $_controller;
    public function __construct($item, $url, $is_moveable, $is_deleteable, $controller)
    {
        $this->is_moveable   = $is_moveable;
        $this->is_deleteable = $is_deleteable;
        $this->_controller   = $controller;
        parent::__construct($item, $url, 'actions', dgettext('tuleap-docman', 'Actions'));
    }

    #[\Override]
    public function getContent($params = [])
    {
        $user = $this->_controller->getUser();

        $content  = '';
        $content .= '<dl>';

        //{{{ New Version
        $content .= $this->item->accept($this);
        //}}}

        //{{{ Move
        $content .= '<dt>' . dgettext('tuleap-docman', 'Move') . '</dt><dd>';
        if (! $this->is_moveable || ! ($this->_controller->userCanWrite($this->item->getId()) && $this->_controller->userCanWrite($this->item->getParentId()))) {
            if ($this->item instanceof \Docman_Folder) {
                $content .= dgettext('tuleap-docman', 'You cannot move this folder.');
            } else {
                $content .= dgettext('tuleap-docman', 'You cannot move this document.');
            }
        } else {
            $move_url = DocmanViewURLBuilder::buildActionUrl(
                $this->item,
                ['default_url' => $this->url],
                ['action' => 'move', 'id' => $this->item->getId()]
            );
            if ($this->item instanceof \Docman_Folder) {
                $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">move this folder</a> to another folder or inside the current folder.'), $move_url);
            } else {
                $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">move this document</a> to another folder or inside the current folder.'), $move_url);
            }
        }
        $content .= '</dd>';
        //}}}

        //{{{ Cut
        $content    .= '<dt>' . dgettext('tuleap-docman', 'Cut') . '</dt><dd>';
        $itemFactory = Docman_ItemFactory::instance($this->item->getGroupId());
        if ($itemFactory->isRoot($this->item)) {
            $content .= dgettext('tuleap-docman', 'You cannot cut this folder.');
        } else {
            $cuturl = DocmanViewURLBuilder::buildActionUrl(
                $this->item,
                ['default_url' => $this->url],
                ['action' => 'action_cut', 'id' => $this->item->getId(), 'orig_action' => 'details', 'orig_id' => $this->item->getId()]
            );
            if ($this->item instanceof \Docman_Folder) {
                $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">cut this folder</a>.'), $cuturl);
            } else {
                $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">cut this document</a>.'), $cuturl);
            }
        }
        $content .= '</dd>';
        //}}}

        //{{{ Copy
        $content .= '<dt>' . dgettext('tuleap-docman', 'Copy') . '</dt><dd>';
        $copyurl  = DocmanViewURLBuilder::buildActionUrl(
            $this->item,
            ['default_url' => $this->url],
            ['action' => 'action_copy', 'id' => $this->item->getId(), 'orig_action' => 'details', 'orig_id' => $this->item->getId()]
        );
        if ($this->item instanceof \Docman_Folder) {
            $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">copy this folder</a>.'), $copyurl);
        } else {
            $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">copy this document</a>.'), $copyurl);
        }
        $content .= '</dd>';
        //}}}

        //{{{ Delete
        $content .= '<dt>' . dgettext('tuleap-docman', 'Delete') . '</dt><dd>';
        if (! $this->is_deleteable || $this->_controller->userCannotDelete($user, $this->item)) {
            if ($this->item instanceof \Docman_Folder) {
                $content .= dgettext('tuleap-docman', 'You cannot delete this folder.');
            } else {
                $content .= dgettext('tuleap-docman', 'You cannot delete this document.');
            }
        } else {
            $delete_url = DocmanViewURLBuilder::buildActionUrl(
                $this->item,
                ['default_url' => $this->url],
                ['action' => 'confirmDelete', 'id' => $this->item->getId()]
            );

            $data_test = 'delete-item-' . $this->item->getId();
            if ($this->item instanceof \Docman_Folder) {
                $content .= sprintf(
                    dgettext(
                        'tuleap-docman',
                        'You can <a href="%1$s" data-test="%s">delete this folder</a>.'
                    ),
                    $delete_url,
                    $data_test
                );
            } else {
                $content .= sprintf(
                    dgettext(
                        'tuleap-docman',
                        'You can <a href="%1$s" data-test="%s">delete this document</a>.'
                    ),
                    $delete_url,
                    $data_test
                );
            }
        }
        $content .= '</dd>';
        //}}}

        $content .= '</dl>';
        return $content;
    }

    #[\Override]
    public function visitFolder(Docman_Folder $item, array $params = []): string
    {
        $content = '';
        if ($this->_controller->userCanWrite($this->item->getid())) {
            $content .= '<dt>' . dgettext('tuleap-docman', 'New document') . '</dt><dd>';
            $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">create a new document</a> in this folder.'), DocmanViewURLBuilder::buildActionUrl($item, ['default_url' => $this->url], ['action' => 'newDocument', 'id' => $item->getId()]));
            $content .= '</dd>';
            $content .= '<dt>' . dgettext('tuleap-docman', 'New folder') . '</dt><dd>';
            $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">create a new folder</a> in this folder.'), DocmanViewURLBuilder::buildActionUrl($item, ['default_url' => $this->url], ['action' => 'newFolder', 'id' => $item->getId()]));
            //{{{ Paste
            $itemFactory  = Docman_ItemFactory::instance($item->getGroupId());
            $copiedItemId = $itemFactory->getCopyPreference($this->_controller->getUser());
            $cutItemId    = $itemFactory->getCutPreference($this->_controller->getUser(), $item->getGroupId());
            $srcItem      = null;
            if ($copiedItemId !== false && $cutItemId === false) {
                $srcItem = $itemFactory->getItemFromDb($copiedItemId);
            } elseif ($copiedItemId === false && $cutItemId !== false && $item->getId() != $cutItemId) {
                $srcItem = $itemFactory->getItemFromDb($cutItemId);
            }
            if ($srcItem && ! $itemFactory->isInSubTree($this->item->getId(), $srcItem->getId())) {
                $content .= '</dd>';
                $content .= '<dt>' . dgettext('tuleap-docman', 'Paste') . '</dt><dd>';
                $copyurl  = DocmanViewURLBuilder::buildActionUrl($item, ['default_url' => $this->url], ['action' => 'action_paste', 'id' => $this->item->getId()]);
                $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">paste \'%2$s\' into this folder</a>.'), $copyurl, $this->hp->purify($srcItem->getTitle(), CODENDI_PURIFIER_CONVERT_HTML));
            }
            //}}}
        }
        $content .= '</dd>';
        return $content;
    }

    #[\Override]
    public function visitItem(Docman_Item $item, array $params = []): string
    {
        $content  = '';
        $content .= '<dt>' . dgettext('tuleap-docman', 'Update') . '</dt><dd>';

        if (! $this->_controller->userCanWrite($this->item->getid())) {
            $content .= dgettext('tuleap-docman', 'You cannot update this document.');
        } else {
            $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">update</a> this document.'), DocmanViewURLBuilder::buildActionUrl($item, ['default_url' => $this->url], ['action' => 'action_update', 'id' => $this->item->getId()]));
        }

        $content .= '</dd>';
        return $content;
    }

    public function visitDocument(Docman_Document $item, array $params = []): string
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitWiki(Docman_Wiki $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitLink(Docman_Link $item, array $params = []): string
    {
        return $this->getSectionForNewVersion();
    }

    #[\Override]
    public function visitFile(Docman_File $item, array $params = []): string
    {
        return $this->getSectionForNewVersion();
    }

    private function getSectionForNewVersion(): string
    {
        $content = '<dt>' . dgettext('tuleap-docman', 'New Version') . '</dt><dd>';
        if (! $this->_controller->userCanWrite($this->item->getid())) {
            $content .= dgettext('tuleap-docman', 'You cannot create a new version.');
        } else {
            $content .= sprintf(dgettext('tuleap-docman', 'You can <a href="%1$s">create a new version</a>.'), DocmanViewURLBuilder::buildActionUrl($this->item, ['default_url' => $this->url], ['action' => 'action_new_version', 'id' => $this->item->getId()]));
        }
        $content .= '</dd>';

        return $content;
    }

    #[\Override]
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): string
    {
        $content = '<textarea name="content" rows="15" cols="50">';
        $version = $item->getCurrentVersion();
        if (is_file($version->getPath())) {
            $content .= file_get_contents($version->getPath());
        }
        $content .= '</textarea>';
        return $this->visitFile($item, array_merge($params, ['input_content' => $content]));
    }

    #[\Override]
    public function visitEmpty(Docman_Empty $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitOtherDocument(\Tuleap\Docman\Item\OtherDocument $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }
}
