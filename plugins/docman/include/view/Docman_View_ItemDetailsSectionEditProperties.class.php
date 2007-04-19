<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
 * $Id$
 */
require_once('Docman_View_ItemDetailsSectionProperties.class.php');

class Docman_View_ItemDetailsSectionEditProperties extends Docman_View_ItemDetailsSectionProperties {
    
    var $token;
    function Docman_View_ItemDetailsSectionEditProperties(&$item, $url, $theme_path, $force, $token) {
        parent::Docman_View_ItemDetailsSectionProperties($item, $url, $theme_path, true, $force);
        $this->token = $token;
    }
    function getContent() {
        $params = array('form_name' => 'update_metadata');
        $html  = '<form name="'.$params['form_name'].'" action="'. $this->url .'" method="post">';
        $html .= parent::getContent($params);
        $html .= '</form>';
        return $html;
    }
    function _showField(&$field) {
        return $field->getField();
    }
    function _getAdditionalRows() {
        $html  = '<tr><td>';
        if ($this->token) {
            $html .= '<input type="hidden" name="token" value="'. $this->token .'" />';
        }
        $html .= '<input type="hidden" name="item[id]" value="'. $this->item->getId() .'" />';
        $html .= '<input type="hidden" name="action" value="update" />';
        $html .= '</td><td>';
        $html .= '<input type="submit" name="confirm" value="'. $GLOBALS['Language']->getText('global','btn_submit') .'" />';
        $html .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global','btn_cancel') .'" />';
        $html .= '</td></tr>';
        return $html;
    }
    
    function _getFieldLabel(&$field) {
        return $field->getLabel(true);
    }
}
?>
