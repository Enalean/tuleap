<?php
/**
 * Copyright © Enalean, 2011 - Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin_Obsolete extends Docman_View_Extra
{

    public function _title($params)
    {
        echo '<h2>' . $this->_getTitle($params) . ' - ' . dgettext('tuleap-docman', 'Manage Obsolete Documents') . '</h2>';
    }

    public function getTable($params)
    {
        $html = '';

        // Get root
        $itemFactory = new Docman_ItemFactory($params['group_id']);
        $rootItem = $itemFactory->getRoot($params['group_id']);

        $nbItemsFound = 0;

        if ($rootItem !== null) {
            $itemIterator = $itemFactory->getItemList(
                $rootItem->getId(),
                $nbItemsFound,
                [
                    'user' => $params['user'],
                    'ignore_collapse' => true,
                    'obsolete_only' => true
                ]
            );
        } else {
            $itemIterator = new ArrayIterator([]);
        }

        $table = html_build_list_table_top(array('Title', 'Obsolete date'));

        $altRowClass = 0;
        $itemIterator->rewind();
        while ($itemIterator->valid()) {
            $item = $itemIterator->current();
            $type = $itemFactory->getItemTypeForItem($item);
            if ($type != PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
                $trclass = html_get_alt_row_color($altRowClass++);
                $table .= "<tr class=\"" . $trclass . "\">\n";

                // Name
                $docmanIcons = $this->_getDocmanIcons($params);
                $icon_src = $docmanIcons->getIconForItem($item, $params);
                $icon = '<img src="' . $icon_src . '" class="docman_item_icon" />';
                $table .= "<td>";
                $table .= '<span style="white-space: nowrap;">';
                $table .= $icon;
                $url    = DocmanViewURLBuilder::buildActionUrl(
                    $item,
                    $params,
                    ['action' => 'details', 'id' => $item->getId()],
                    false,
                    true
                );
                $table .= '<a href="' . $url . '">';
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

    public function _content($params)
    {
        $html = '';

        $html .= '<p>';
        $html .= dgettext('tuleap-docman', 'This is the list of all documents obsolete today. If you click on document title you will be redirected to the document properties and you will be able to make it available again.');
        $html .= '</p>';

        $html .= $this->getTable($params);

        print $html;
    }
}
