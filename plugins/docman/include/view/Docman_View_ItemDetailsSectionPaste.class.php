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

class Docman_View_ItemDetailsSectionPaste 
extends Docman_View_ItemDetailsSectionActions {
    var $force;
    
    function Docman_View_ItemDetailsSectionPaste(&$item, $url, &$controller,
                                                 $force) {
        parent::Docman_View_ItemDetailsSectionActions($item, $url, false,
                                                      true, $controller);
        $this->force = $force;
    }

    function getContent() {
        return $this->item->accept($this);
    }
    
    function visitFolder($item, $params = array()) {
        $itemFactory =& Docman_ItemFactory::instance($item->getGroupId());
        $brotherIter = $itemFactory->getChildrenFromParent($this->item);

        $selectedValue = 'beginning';

        $content = '';

        $content .= '<dl><dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_actions_paste') .'</dt><dd>';
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

        $brotherIter->rewind();
        while($brotherIter->valid()) {
            $item = $brotherIter->current();
            
            $vals[$i]  = $item->getRank()+1;
            $texts[$i] = $GLOBALS['Language']->getText('plugin_docman', 'details_paste_rank_after').' '.$item->getTitle();
            $i++;

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

        $content .= '<input type="submit" name="submit" value="'.$GLOBALS['Language']->getText('plugin_docman', 'details_paste_paste_button').'" />';

        $content .= '</form>';
        
        $content .= '</dl>';
        
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

}

?>
