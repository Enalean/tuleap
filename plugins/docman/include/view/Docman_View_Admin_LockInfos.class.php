<?php
/**
 * Copyright � STMicroelectronics, 2009. All Rights Reserved.
 * 
 * Originally written by Sabri LABBENE 2009.
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'Docman_View_Extra.class.php';

class Docman_View_Admin_LockInfos extends Docman_View_Extra {
    var $defaultUrl;

    function _title($params) {
        echo '<h2>'. $this->_getTitle($params) .' - '. $GLOBALS['Language']->getText('plugin_docman', 'admin_lock_infos_title') .'</h2>';
    }

    function _content($params) {
        $html = '';

        // Display help message
        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_lock_infos_help');
        $html .= '</p>';

        $html .= $this->getTable($params);

        print($html);
    }

    function getTable($params) {
        $this->defaultUrl = $params['default_url'];
        $content = '';

        $content .= html_build_list_table_top(array($GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_title'),
                                                    $GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_location'),
                                                    $GLOBALS['Language']->getText('plugin_docman', 'admin_lock_infos_who'),
                                                    $GLOBALS['Language']->getText('plugin_docman', 'admin_lock_infos_when')
                                            ));

        // Get list of all locked documents in the project.
        $dPM = Docman_PermissionsManager::instance($params['group_id']);
        $lockInfos = $dPM->getLockFactory()->getProjectLockInfos($params['group_id']);

        $uH = UserHelper::instance();
        $hp = Codendi_HTMLPurifier::instance();

        require_once(dirname(__FILE__) . '/../Docman_ItemFactory.class.php');
        $dIF = new Docman_ItemFactory($params['group_id']);


        $altRowClass = 0;
        foreach($lockInfos as $row) {
            $trclass = html_get_alt_row_color($altRowClass++);
            $item = $dIF->getItemFromDb($row['item_id']);
            $parent = $dIF->getItemFromDb($item->getParentId());
            $content .= '<tr class="'.$trclass.'">';
            $content .= '<td>'. '<a href="/plugins/docman/?group_id='. $params['group_id']. '&action=details&id='. $item->getId(). '">'. $item->getTitle() .'</a></td>';
            $content .= '<td>';
            if($dIF->isRoot($parent)){
                $content .= '</td>';
            } else {
                $content .=  '<a href="'. $this->defaultUrl. '&action=show&id='. $parent->getId(). '">' .$parent->getTitle() .'</a></td>';
            }
            $content .= '<td>'. $hp->purify($uH->getDisplayNameFromUserId($row['user_id'])) .'</td>';
            $content .= '<td>'. format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['lock_date']) .'</td>';
            $content .= '</tr>';
        }

        $content .= '</table>';

        return $content;
    }
}
?>