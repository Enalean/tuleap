<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
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
 * 
 */

require_once 'DocmanWatermark_LogDao.class.php';
require_once 'common/dao/CodendiDataAccess.class.php';

class DocmanWatermark_Log {
    
    function getLog($item) {
        $dao = $this->getDao();
        return $dao->searchByItemId($item->getId());
    }
    
    function disableWatermarking($item, $user) {
        $dao = $this->getDao();
        $dao->disableWatermarking($item->getId(), $user->getId());
    }
    
    function enableWatermarking($item, $user) {
        $dao = $this->getDao();
        $dao->enableWatermarking($item->getId(), $user->getId());
    }
    
    /**
     * Wrapper for DocmanWatermark_LogDao
     * @return DocmanWatermark_LogDao
     */
    function getDao() {
        return new DocmanWatermark_LogDao(CodendiDataAccess::instance());
    }
}

?>