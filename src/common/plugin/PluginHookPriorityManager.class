<?php
require_once('common/dao/PriorityPluginHookDao.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * PluginHookPriorityManager
 */
class PluginHookPriorityManager {
    var $priorityPluginHookDao;

    function PluginHookPriorityManager() {
    }
    
    function getPriorityForPluginHook(&$plugin, $hook) {
        $priority_dao =& $this->_getPriorityPluginHookDao();
        $priority = 0;
        if ($dar =& $priority_dao->searchByHook_PluginId($hook, $plugin->getId())) {
            if ($row = $dar->getRow()) {
                $priority = (int)$row['priority'];
            }
        }
        return $priority;
    }
    
    function setPriorityForPluginHook(&$plugin, $hook, $priority) {
        $priority_dao =& $this->_getPriorityPluginHookDao();
        return $priority_dao->setPriorityForHook_PluginId($hook, $plugin->getId(), $priority);
    }
    
    function &_getPriorityPluginHookDao() {
        if (!is_a($this->priorityPluginHookDao, 'PriorityPluginHookDao')) {
            $this->priorityPluginHookDao =& new PriorityPluginHookDao(CodexDataAccess::instance());
        }
        return $this->priorityPluginHookDao;
    }
    
    function removePlugin(&$plugin) {
        $priority_dao =& $this->_getPriorityPluginHookDao();
        return $priority_dao->deleteByPluginId($plugin->getId());
    }
}
?>
