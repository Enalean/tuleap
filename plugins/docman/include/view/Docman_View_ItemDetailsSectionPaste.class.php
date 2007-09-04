<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
 * 
 */

require_once('Docman_View_ItemDetailsSectionActions.class.php');
require_once(dirname(__FILE__).'/../Docman_PermissionsManager.class.php');
require_once(dirname(__FILE__).'/../Docman_MetadataComparator.class.php');
require_once('common/include/UserManager.class.php');

class Docman_View_ItemDetailsSectionPaste 
extends Docman_View_ItemDetailsSectionActions {
    var $itemToPaste;
    var $srcGo;
    var $dstGo;

    function Docman_View_ItemDetailsSectionPaste(&$item, $url, &$controller,
                                                 $itemToPaste) {
        parent::Docman_View_ItemDetailsSectionActions($item, $url, false,
                                                      true, $controller);
        $this->itemToPaste = $itemToPaste;
        $this->srcGo = group_get_object($this->itemToPaste->getGroupId());
        $this->dstGo = group_get_object($item->getGroupId());
    }

    function checkMdDifferences(&$mdDiffers) {
        $html = '';

        $mdCmp = new Docman_MetadataComparator($this->srcGo->getGroupId(),
                                               $this->dstGo->getGroupId(),
                                               $this->_controller->getThemePath());
        $cmpTable = $mdCmp->getMetadataCompareTable($sthToImport);
        if($sthToImport) {
            $html .= '<h2>'. $GLOBALS['Language']->getText('plugin_docman', 'details_paste_mddiff_title') .'</h2>';
            $dPm =& Docman_PermissionsManager::instance($this->dstGo->getGroupId());
            if($dPm->currentUserCanAdmin()) {
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

    function getContent() {
        return $this->item->accept($this);
    }

    function visitFolder($item, $params = array()) {
        $content = '';

        // First Check metadata differences
        $mdDiffers = false;
        $content = $this->checkMdDifferences($mdDiffers);

        // Paste
        $itemFactory =& Docman_ItemFactory::instance($item->getGroupId());
        $brotherIter = $itemFactory->getChildrenFromParent($this->item);

        $selectedValue = 'beginning';        

        $content .= '<h2>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_paste') .'</h2>';
        $content .= '<form name="select_paste_location" method="POST" action="?">';
        $content .= '<input type="hidden" name="action" value="paste" />';
        $content .= '<input type="hidden" name="group_id" value="'.$this->item->getGroupId().'" />';
        $content .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
        $content .= '<p>Location ';

        $vals = array('beginning', 'end', '--');
        $texts = array($GLOBALS['Language']->getText('plugin_docman', 'details_paste_rank_beg'), 
                       $GLOBALS['Language']->getText('plugin_docman', 'details_paste_rank_end'), 
                       '----');
        $i = 3;
        
        $pm =& Docman_PermissionsManager::instance($item->getGroupId());
        $um =& UserManager::instance();
        $user =& $um->getCurrentUser();
        
        $brotherIter->rewind();
        while($brotherIter->valid()) {
            $item = $brotherIter->current();
            if ($pm->userCanWrite($user, $item->getId())) {
                $vals[$i]  = $item->getRank()+1;
                $texts[$i] = $GLOBALS['Language']->getText('plugin_docman', 'details_paste_rank_after').' '.$item->getTitle();
                $i++;
            }
            $brotherIter->next();
        }

        // Cannot use html_build_select_box_from_arrays because of to lasy == operator
        // In this case because of cast string values are converted to 0 on cmp. So if
        // there is a rank == 0 ... so bad :/
        $content .= '<select name="rank">'."\n";
        $maxOpts = count($vals);
        for($i = 0; $i < $maxOpts; $i++) {
            $selected = '';
            if($vals[$i] === $selectedValue) {
                $selected = ' selected="selected"';
            }
            $content .= '<option value="'.$vals[$i].'"'.$selected.'>'.$texts[$i].'</option>'."\n";
        }
        $content .= '</select>';
        $content .= '</p>';

        
        if($mdDiffers == 'admin') {
            $content .= '<p>';
            $content .= $GLOBALS['Language']->getText('plugin_docman', 'details_paste_importmd', array($this->srcGo->getPublicName()));
            $content .= ' ';
            $content .= '<input type="checkbox" checked="checked" name="import_md" value="1" />';
            $content .= '</p>';
        }

        $buttonTxt = $GLOBALS['Language']->getText('plugin_docman', 'details_paste_button_paste');
        if($mdDiffers == 'user') {
            $buttonTxt = $GLOBALS['Language']->getText('plugin_docman', 'details_paste_button_pasteanyway');
        }
        $content .= '<input type="submit" name="submit" value="'.$buttonTxt.'" />';
        $content .= ' ';
        $content .= '<input type="submit" name="cancel" value="'.$GLOBALS['Language']->getText('global', 'btn_cancel').'" />';

        $content .= '</form>';
        
        return $content;
    }

    function visitDocument($item, $params = array()) {
        return '';
    }

    function visitWiki($item, $params = array()) {
        return '';
    }

    function visitLink($item, $params = array()) {
        return '';
    }

    function visitFile($item, $params = array()) {
        return '';
    }

    function visitEmbeddedFile($item, $params = array()) {
        return '';
    }

    function visitEmpty($item, $params = array()) {
        return '';
    }

    function &_getDocmanIcons() {
        $icons = new Docman_Icons($this->_controller->getThemePath().'/images/ic/');
        return $icons;
    }

}

?>
