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

}

?>