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

require_once('common/dao/ServerDao.class.php');
require_once('common/server/Server.class.php');

/**
* ServerFactory
*/
class ServerFactory {
    
    function ServerFactory() {
    }
    function getAllServers() {
        $servers = array();
        $dao =& new ServerDao(CodendiDataAccess::instance());
        $dar =& $dao->searchAll();
        if ($dar) {
            while($dar->valid()) {
                $row = $dar->current();
                $servers[] = new Server($row);
                $dar->next();
            }
        }
        return $servers;
    }
    function delete($id) {
        $dao =& new ServerDao(CodendiDataAccess::instance());
        return $dao->delete($id);
    }
    function create($arr) {
        if (!$arr || !is_array($arr)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('server', 'error_missingparams'));
        } else if ($this->validate($arr)) {
            if ($this->getServerById($arr['id'])) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('server', 'error_alreadyid', array($arr['id'])));
            } else {
                $dao =& new ServerDao(CodendiDataAccess::instance());
                return $dao->create($arr);
            }
        }
        return false;
    }
    function validate($arr) {
        if ($this->_field_is_empty($arr, 'id')) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('server', 'error_emptyid'));
            return false;
        } else if (is_numeric($arr['id']) && (int)$arr['id'] != $arr['id']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('server', 'error_integerid'));
            return false;
        } else if ($this->_field_is_empty($arr, 'name')) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('server', 'error_emptyname'));
            return false;
        } else if ($this->_field_is_empty($arr, 'http') && $this->_field_is_empty($arr, 'https')) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('server', 'error_missinghttp'));
            return false;
        }
        return true;
    }
    function _field_is_empty($tab, $field) {
        return !isset($tab[$field]) || !trim($tab[$field]);
    }
    function getServerById($id) {
        $s = null;
        $dao =& new ServerDao(CodendiDataAccess::instance());
        $dar =& $dao->searchById($id);
        if ($dar && $dar->valid()) {
            $row = $dar->current();
            $s = new Server($row);
        }
        return $s;
    }
    function getMasterServer() {
        $s = null;
        $dao =& new ServerDao(CodendiDataAccess::instance());
        $dar =& $dao->searchByIsMaster($is_master = true);
        if ($dar && $dar->valid()) {
            $row = $dar->current();
            $s = new Server($row);
        }
        return $s;
    }
    function update($server_id, $arr) {
        if (!$arr || !is_array($arr)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('server', 'error_missingparams'));
        } else if ($this->validate($arr)) {
            $dao =& new ServerDao(CodendiDataAccess::instance());
            return $dao->modify($server_id, $arr);
        }
        return false;
    }
    function setMaster($server_id) {
        $dao =& new ServerDao(CodendiDataAccess::instance());
        return $dao->setMaster($server_id);
    }
}
?>
