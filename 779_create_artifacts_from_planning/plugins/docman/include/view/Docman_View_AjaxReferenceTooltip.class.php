<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('Docman_View_View.class.php');

class Docman_View_AjaxReferenceTooltip extends Docman_View_View {
    function display($params = array()) {
        $di = $this->_getDocmanIcons($params);
        $icon_src = $di->getIconForItem($params['item'], $params);
        echo '<img src="'. $icon_src .'" class="docman_item_icon" style="vertical-align:middle; text-decoration:none;" />';
        
        echo '<strong style="padding-left:6px;">'. $params['item']->getTitle() .'</strong>';
        
        $desc = $params['item']->getDescription();
        if ($desc) {
            echo '<br />'. $desc;
        }
    }
}
?>
