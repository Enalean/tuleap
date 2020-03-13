<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Docman_View_ItemDetailsSectionPaste extends Docman_View_ItemDetailsSectionActions
{
    public $itemToPaste;
    public $srcGo;
    public $dstGo;
    private $mode;

    public function __construct(
        $item,
        $url,
        $controller,
        $itemToPaste,
        $mode
    ) {
        parent::__construct(
            $item,
            $url,
            false,
            true,
            $controller
        );

        $this->itemToPaste = $itemToPaste;
        $pm = ProjectManager::instance();
        $this->srcGo = $pm->getProject($this->itemToPaste->getGroupId());
        $this->dstGo = $pm->getProject($item->getGroupId());
        $this->mode  = $mode;
    }

    public function checkMdDifferences(&$mdDiffers)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html = '';

        $mdCmp = new Docman_MetadataComparator(
            $this->srcGo->getGroupId(),
            $this->dstGo->getGroupId(),
            $this->_controller->getThemePath()
        );
        $cmpTable = $mdCmp->getMetadataCompareTable($sthToImport);
        if ($sthToImport) {
            $html .= '<h2>' . dgettext('tuleap-docman', 'Document properties') . '</h2>';
            $dPm = Docman_PermissionsManager::instance($this->dstGo->getGroupId());
            $current_user = UserManager::instance()->getCurrentUser();
            if ($dPm->userCanAdmin($current_user)) {
                $mdDiffers = 'admin';
                $html .= $cmpTable;
            } else {
                $mdDiffers = 'user';
                $docmanIcons = $this->_getDocmanIcons();
                $html .= sprintf(dgettext('tuleap-docman', '<p><img src="%3$s" /> There are differences in properties definitions between %1$s and %2$s. You <strong>may loose</strong> some <strong>properties informations</strong>.</p><p>You may either:<ul><li>Paste anyway, so only properties that exists in both %1$s and %2$s will be copied.</li><li>Ask to a document manager admin to import %2$s properties definitions</li></ul></p><p><strong>Note:</strong> This <strong>doesn\'t impact documents</strong> themselves. This only refers to properties.</p>'), $purifier->purify($this->srcGo->getPublicName()), $purifier->purify($this->dstGo->getPublicName()), $docmanIcons->getThemeIcon('warning.png'));
            }
        }

        return $html;
    }

    public function getContent($params = [])
    {
        return $this->item->accept($this);
    }

    public function visitFolder($item, $params = array())
    {
        $content = '';

        // First Check metadata differences
        $mdDiffers = false;
        if ($this->mode === 'copy') {
            $content = $this->checkMdDifferences($mdDiffers);
        }

        $content .= '<h2>' . dgettext('tuleap-docman', 'Paste') . '</h2>';

        $content .= '<p>';
        if ($this->mode === 'copy') {
            $content .= dgettext('tuleap-docman', 'You are about to <strong>paste</strong> an item you <strong>copied</strong>. It will clone the whole hierarchy but <strong>only keeps the latest version of files, doesn\'t keep approval tables nor notifications and inherit permissions from new parent</strong>.');
        } else {
            $content .= dgettext('tuleap-docman', 'You are about to <strong>paste</strong> an item you <strong>cut</strong>. It will preserve the whole hierarchy, notifications, approval table, permissions, etc. Actually, only the parent of the item will change.');
        }
        $content .= '</p>';

        $content .= '<form name="select_paste_location" method="POST" action="?">';
        $content .= '<input type="hidden" name="action" value="paste" />';
        $content .= '<input type="hidden" name="group_id" value="' . $this->item->getGroupId() . '" />';
        $content .= '<input type="hidden" name="id" value="' . $this->item->getId() . '" />';
        $content .= '<p>';
        $itemRanking = new Docman_View_ItemRanking();
        $itemRanking->setDropDownName('rank');
        $content .= $itemRanking->getDropDownWidget($this->item);
        $content .= '</p>';

        $purifier = Codendi_HTMLPurifier::instance();
        if ($this->mode == 'copy' && $mdDiffers == 'admin') {
            $content .= '<p>';
            $content .= sprintf(dgettext('tuleap-docman', 'Import properties from %1$s:'), $purifier->purify($this->srcGo->getPublicName()));
            $content .= ' ';
            $content .= '<input type="checkbox" checked="checked" name="import_md" value="1" />';
            $content .= '</p>';
        }

        $buttonTxt = dgettext('tuleap-docman', 'Paste');
        if ($this->mode == 'copy' && $mdDiffers == 'user') {
            $buttonTxt = dgettext('tuleap-docman', 'Paste Anyway');
        }
        $content .= '<input type="submit" name="submit" value="' . $buttonTxt . '" />';
        $content .= ' ';
        $content .= '<input type="submit" name="cancel" value="' . $GLOBALS['Language']->getText('global', 'btn_cancel') . '" />';

        $content .= '</form>';
        return $content;
    }

    public function visitDocument($item, $params = array())
    {
        return '';
    }

    public function visitWiki($item, $params = array())
    {
        return '';
    }

    public function visitLink($item, $params = array())
    {
        return '';
    }

    public function visitFile($item, $params = array())
    {
        return '';
    }

    public function visitEmbeddedFile($item, $params = array())
    {
        return '';
    }

    public function visitEmpty($item, $params = array())
    {
        return '';
    }

    public function &_getDocmanIcons()
    {
        $icons = new Docman_Icons($this->_controller->getThemePath() . '/images/ic/');
        return $icons;
    }
}
