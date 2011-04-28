<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
require_once('common/dao/NotificationsDao.class.php');
/* abstract */ class NotificationsManager { /* implements EventListener */

    function NotificationsManager() {
        $this->dao =& $this->_getDao();
    }
    
    function add($user_id, $object_id, $type = null) {
        if ($type === null) {
            $type = $this->_getType();
        }
        return $this->dao->create($user_id, $object_id, $type);
    }
    function remove($user_id, $object_id, $type = null) {
        if ($type === null) {
            $type = $this->_getType();
        }
        return $this->dao->delete($user_id, $object_id, $type);
    }
    function exist($user_id, $object_id, $type = null) {
        if ($type === null) {
            $type = $this->_getType();
        }
        $dar =& $this->dao->search($user_id, $object_id, $type);
        return $dar->valid();
    }
    
    /* abstract */ function somethingHappen($event, $params) {
    }
    
    /* abstract  */function sendNotifications($event, $params) {
    }
    
    /* abstract protected */ function _getType() {
    }
    
    var $dao;
    function &_getDao() {
        if (!$this->dao) {
            $this->dao = new NotificationsDao(CodendiDataAccess::instance());
        }
        return  $this->dao;
    }
}

?>