<?php
/**
 * Copyright © STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
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
 * 
 * 
 */

class Docman_View_LoveDetails {
    var $md;

    function Docman_View_LoveDetails($md) {
        $this->md = $md;
    }

    function getNameField($value='') {
        $html = '';

        $html .=  '<p>';
        $html .= "\n";
        $html .=  '<label>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_val_create_name').'</label>';
        $html .= "\n";
        $html .=  '<input name="name" type="text" value="'.$value.'" class="text_field" />';
        $html .= "\n";
        $html .=  '</p>';
        $html .= "\n";

        return $html;
    }

    function getDescriptionField($value='') {
        $html = '';

        $html .=  '<p>';
        $html .= "\n";
        $html .=  '<label>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_val_create_desc').'</label>';
        $html .= "\n";
        $html .=  '<textarea name="descr">'.$value.'</textarea>';
        $html .= "\n";
        $html .=  '</p>';
        $html .= "\n";

        return $html;
    }

    function getRankField($value='end') {
        $html = '';

        $html .=  '<p>';
        $html .= "\n";
        $html .=  '<label>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_val_create_rank').'</label>';
        $html .= "\n";

        $vals = array('beg', 'end', '--');
        $texts = array($GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_val_create_rank_beg'), 
                       $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_val_create_rank_end'), 
                       '----');
        $i = 3;

        $vIter =& $this->md->getListOfValueIterator();
        $vIter->rewind();
        while($vIter->valid()) {
            $e =& $vIter->current();
                
            if($e->getStatus() == 'A' 
               || $e->getStatus() == 'P') {
                    
                $vals[$i]  = $e->getRank()+1;
                $texts[$i] = $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_val_create_rank_after').' '.Docman_MetadataHtmlList::_getElementName($e);
                $i++;
            }
                
            $vIter->next();
        }                           
        $html .=  html_build_select_box_from_arrays($vals, $texts, 'rank', $value, false, ''); 
        $html .=  '</p>';
        $html .= "\n";

        return $html;
    }

    function getHiddenFields($loveId=null) {
        $html = '';

        $html .= '<input type="hidden" name="md" value="'.$this->md->getLabel().'" />';
        $html .= "\n";

        if($loveId !== null) {
            $html .= '<input type="hidden" name="loveid" value="'.$loveId.'" />';
            $html .= "\n";
        }
        
        return $html;
    }

}

?>
