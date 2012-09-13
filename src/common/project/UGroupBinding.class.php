<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/dao/UGroupDao.class.php');

/**
 * UGroup object
 */
class UGroupBinding {

    protected $_ugroupdao;

    /**
     * Obtain UGroupDao
     *
     * @raturn UGroupDao
     */
    protected function getUGroupDao() {
        if (!$this->_ugroupdao) {
            $this->_ugroupdao = new UGroupDao();
        }
        return $this->_ugroupdao;
    }

    /**
     * Check if the user group is binded
     *
     * @param Integer $ugroupId Id of the user goup
     *
     * @return Boolean
     */
     public function isBinded($ugroupId) {
         return false;
     }

    /**
     * Get title of the link to binding interface
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return String
     */
    public function getLinkTitle($ugroupId) {
        if ($this->isBinded($groupId)) {
            // @TODO: i18n
            return '- Update binding';
        } else {
            // @TODO: i18n
            return '- Add binding';
        }
    }

    /**
     * Perform actions on user group binding
     *
     * @param Integer         $ugroupId Id of the user group
     * @param Codendi_Request $request  the HTTP request
     *
     * @return Void
     */
    public function processRequest($ugroupId, Codendi_Request $request) {
        $func = $request->getValidated('action', new Valid_WhiteList('add_binding', 'remove_binding'), null);
        if ($func) {
            // @TODO: i18n
            $GLOBALS['Response']->addFeedback('info', 'Action performed');
        }
    }

    /**
     * The form that will be displayed to add/edit user group binding
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return String
     */
    public function getHTMLContent($ugroupId) {
        $html = '<form action="" method="post">';
        $html .= '<input type="hidden" name="action" value="add_binding" />';
        $html .= '<table>';
        // @TODO: i18n
        $html .= '<tr><td>Source user group</td><td><input name="source_ugroup" /></td></tr>';
        // @TODO: i18n
        $html .= '<tr><td></td><td><input type="submit" value="Add binding"/></td></tr>';
        $html .= '</table>';
        $html .= '</form>';
        return $html;
    }

}

?>