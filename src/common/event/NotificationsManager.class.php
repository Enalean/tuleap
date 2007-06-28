<?php
/**
 * Copyright (c) Xerox, 2006. All Rights Reserved.
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
 * 
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
            $this->dao = new NotificationsDao(CodexDataAccess::instance());
        }
        return  $this->dao;
    }
}

?>