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
    var $itemToPaste;
    var $srcGo;
    var $dstGo;
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

    function checkMdDifferences(&$mdDiffers)
    {
        $html = '';

        $mdCmp = new Docman_MetadataComparator(
            $this->srcGo->getGroupId(),
            $this->dstGo->getGroupId(),
            $this->_controller->getThemePath()
        );
        $cmpTable = $mdCmp->getMetadataCompareTable($sthToImport);
        if ($sthToImport) {
            $html .= '<h2>'. $GLOBALS['Language']->getText('plugin_docman', 'details_paste_mddiff_title') .'</h2>';
            $dPm = Docman_PermissionsManager::instance($this->dstGo->getGroupId());
            $current_user = UserManager::instance()->getCurrentUser();
            if ($dPm->userCanAdmin($current_user)) {
                $mdDiffers = 'admin';
                $html .= $cmpTable;
            } else {
                $mdDiffers = 'user';
                $docmanIcons = $this->_getDocmanIcons();
                $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_paste_mddiff_noadmin', array($this->srcGo->getPublicName(), $this->dstGo->getPublicName(), $docmanIcons->getThemeIcon('warning.png')));
            }
        }

        return $html;
    }

    function getContent($params = [])
    {
        return $this->item->accept($this);
    }

    function visitFolder($item, $params = array())
    {
        $content = '';

        // First Check metadata differences
        $mdDiffers = false;
        if ($this->mode == 'copy') {
            $content = $this->checkMdDifferences($mdDiffers);
        }

        $content .= '<h2>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_paste') .'</h2>';

        $content .= '<p>';
        $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_paste_from_'.$this->mode);
        $content .= '</p>';

        $content .= '<form name="select_paste_location" method="POST" action="?">';
        $content .= '<input type="hidden" name="action" value="paste" />';
        $content .= '<input type="hidden" name="group_id" value="'.$this->item->getGroupId().'" />';
        $content .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
        $content .= '<p>';
        $itemRanking = new Docman_View_ItemRanking();
        $itemRanking->setDropDownName('rank');
        $content .= $itemRanking->getDropDownWidget($this->item);
        $content .= '</p>';

        if ($this->mode == 'copy' && $mdDiffers == 'admin') {
            $content .= '<p>';
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_paste_importmd', array($this->srcGo->getPublicName()));
            $content .= ' ';
            $content .= '<input type="checkbox" checked="checked" name="import_md" value="1" />';
            $content .= '</p>';
        }

        $buttonTxt = $GLOBALS['Language']->getText('plugin_docman', 'details_paste_button_paste');
        if ($this->mode == 'copy' && $mdDiffers == 'user') {
            $buttonTxt = $GLOBALS['Language']->getText('plugin_docman', 'details_paste_button_pasteanyway');
        }
        $content .= '<input type="submit" name="submit" value="'.$buttonTxt.'" />';
        $content .= ' ';
        $content .= '<input type="submit" name="cancel" value="'.$GLOBALS['Language']->getText('global', 'btn_cancel').'" />';

        $content .= '</form>';
        return $content;
    }

    function visitDocument($item, $params = array())
    {
        return '';
    }

    function visitWiki($item, $params = array())
    {
        return '';
    }

    function visitLink($item, $params = array())
    {
        return '';
    }

    function visitFile($item, $params = array())
    {
        return '';
    }

    function visitEmbeddedFile($item, $params = array())
    {
        return '';
    }

    function visitEmpty($item, $params = array())
    {
        return '';
    }

    function &_getDocmanIcons()
    {
        $icons = new Docman_Icons($this->_controller->getThemePath().'/images/ic/');
        return $icons;
    }
}
