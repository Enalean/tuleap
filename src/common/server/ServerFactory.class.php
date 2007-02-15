<?php 

require_once('common/dao/ServerDao.class.php');
require_once('common/server/Server.class.php');

/**
* ServerFactory
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class ServerFactory {
    
    
    function getAllServers() {
        $servers = array();
        $dao =& new ServerDao(CodeXDataAccess::instance());
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
        $dao =& new ServerDao(CodeXDataAccess::instance());
        return $dao->delete($id);
    }
    function create($arr) {
        if (!$arr || !is_array($arr)) {
            $GLOBALS['Response']->addFeedback('error', 'Missing parameters');
        } else if ($this->validate($arr)) {
            $dao =& new ServerDao(CodeXDataAccess::instance());
            return $dao->create($arr);
        }
        return false;
    }
    function validate($arr) {
        if (!isset($arr['name']) || !trim($arr['name'])) {
            $GLOBALS['Response']->addFeedback('error', 'Name cannot be empty');
            return false;
        }
        return true;
    }
    function getServerById($id) {
        $s = null;
        $dao =& new ServerDao(CodeXDataAccess::instance());
        $dar =& $dao->searchById($id);
        if ($dar && $dar->valid()) {
            $row = $dar->current();
            $s = new Server($row);
        }
        return $s;
    }
    function update($arr) {
        if (!$arr || !is_array($arr)) {
            $GLOBALS['Response']->addFeedback('error', 'Missing parameters');
        } else if ($this->validate($arr)) {
            $dao =& new ServerDao(CodeXDataAccess::instance());
            return $dao->modify($arr);
        }
        return false;
    }
}
?>
