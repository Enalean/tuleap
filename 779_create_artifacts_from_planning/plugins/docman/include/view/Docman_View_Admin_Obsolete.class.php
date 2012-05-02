<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
 *
 * $Id$
 */

require_once('Docman_View_Extra.class.php');

class Docman_View_Admin_Obsolete extends Docman_View_Extra {

    function _title($params) {
        echo '<h2>'. $this->_getTitle($params) .' - '. $GLOBALS['Language']->getText('plugin_docman', 'admin_obsolete_title') .'</h2>';
    }

    function getTable($params) {
        $html = '';

        // Get root
        $itemFactory = new Docman_ItemFactory($params['group_id']);
        $rootItem = $itemFactory->getRoot($params['group_id']);

        $nbItemsFound = 0;

        $itemIterator =& $itemFactory->getItemList($rootItem->getId(),
                                              $nbItemsFound,
                                              array('user' => $params['user'],
                                                    'ignore_collapse' => true,
                                                    'obsolete_only' => true));

        $table = html_build_list_table_top(array('Title', 'Obsolete date'));

        $altRowClass = 0;
        $itemIterator->rewind();
        while($itemIterator->valid()) {            
            $item =& $itemIterator->current();
            $type = $itemFactory->getItemTypeForItem($item);
            if($type != PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
                $trclass = html_get_alt_row_color($altRowClass++);
                $table .= "<tr class=\"".$trclass."\">\n";

                // Name
                $docmanIcons =& $this->_getDocmanIcons($params);
                $icon_src = $docmanIcons->getIconForItem($item, $params);
                $icon = '<img src="'. $icon_src .'" class="docman_item_icon" />';
                $table .= "<td>";
                $table .= '<span style="white-space: nowrap;">';
                $table .= $icon;
                $url = $this->buildActionUrl($params,
                                             array('action' => 'details',
                                                   'id' => $item->getId()),
                                             false,
                                             true);
                $table .= '<a href="'.$url.'">';
                $table .= htmlentities($item->getTitle(), ENT_QUOTES, 'UTF-8');
                $table .= '</a>';
                $table .= '</span>';
                $table .= "</td>\n";

                // Obsolete date
                $table .= "<td>";
                $table .= format_date("Y-m-j", $item->getObsolescenceDate());
                $table .= "</td>\n";

                $table .= "</tr>\n";
            }
            $itemIterator->next();
        }

        $table .= "</table>\n";

        $html = $table;

        return $html;
    }

    function _content($params) {
        $html = '';

        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_obsolete_help');
        $html .= '</p>';

        $html .= $this->getTable($params);

        print $html;
    }

}

?>
