<?php
/**
 * Copyright © STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once(dirname(__FILE__).'/../Docman_PermissionsManager.class.php');
require_once(dirname(__FILE__).'/../Docman_ItemFactory.class.php');
require_once('common/include/UserManager.class.php');

class Docman_View_ItemRanking {
    var $dropDownName;
    var $selectedValue;

    function Docman_View_ItemRanking() {
        $this->selectedValue = 'beginning';
    }

    function setDropDownName($v) {
        $this->dropDownName = $v;
    }


    function setSelectedValue($v) {
        if(is_numeric($v)) {
            $this->selectedValue = (int) $v;
        } else {
            $this->selectedValue = $v;
        }
    }

    function getDropDownWidget($parentItem) {
        $itemFactory =& Docman_ItemFactory::instance($parentItem->getGroupId());
        $brotherIter = $itemFactory->getChildrenFromParent($parentItem);

        $vals = array('beginning', 'end', '--');
        $texts = array($GLOBALS['Language']->getText('plugin_docman', 'view_itemrank_beg'),
                       $GLOBALS['Language']->getText('plugin_docman', 'view_itemrank_end'),
                       '----');
        $i = 3;

        $pm =& Docman_PermissionsManager::instance($parentItem->getGroupId());
        $um =& UserManager::instance();
        $user =& $um->getCurrentUser();

        $brotherIter->rewind();
        while($brotherIter->valid()) {
            $item = $brotherIter->current();
            if ($pm->userCanWrite($user, $item->getId())) {
                $vals[$i]  = $item->getRank()+1;
                $texts[$i] = $GLOBALS['Language']->getText('plugin_docman', 'view_itemrank_after').' '.$item->getTitle();
                $i++;
            }
            $brotherIter->next();
        }

        // Cannot use html_build_select_box_from_arrays because of to lasy == operator
        // In this case because of cast string values are converted to 0 on cmp. So if
        // there is a rank == 0 ... so bad :/
        $html = '';
        $html = $GLOBALS['Language']->getText('plugin_docman', 'view_itemrank_position').' ';

        $html .= '<select name="'.$this->dropDownName.'">'."\n";
        $maxOpts = count($vals);
        for($i = 0; $i < $maxOpts; $i++) {
            $selected = '';
            if($vals[$i] === $this->selectedValue) {
                $selected = ' selected="selected"';
            }
            $html .= '<option value="'.$vals[$i].'"'.$selected.'>'.$texts[$i].'</option>'."\n";
        }
        $html .= '</select>';

        return $html;
    }

}

?>
