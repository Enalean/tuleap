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

/**
 * PluginHookPriorityManager
 */
class PluginHookPriorityManager {
    var $priorityPluginHookDao;
    var $priorityCache;

    function PluginHookPriorityManager() {
        $this->priorityCache = null;
    }
    
    function cacheAllPrioritiesForPluginHook() {
        $this->priorityCache = array();
        $priority_dao = $this->_getPriorityPluginHookDao();
        $dar = $priority_dao->searchPrioritiesForAllPlugins();
        if($dar && !$dar->isError()) {
            while($dar->valid()) {
                $row = $dar->current();
                $this->priorityCache[$row['id']][$row['hook']] = (int)$row['priority'];
                $dar->next();
            }
        }
    }
    
    function getPriorityForPluginHook(&$plugin, $hook) {
        $priority = 0;
        if($this->priorityCache !== null) {
            if(isset($this->priorityCache[$plugin->getId()][$hook])) {
                $priority = $this->priorityCache[$plugin->getId()][$hook];
            }
        } else {
            $priority_dao = $this->_getPriorityPluginHookDao();
            if ($dar = $priority_dao->searchByHook_PluginId($hook, $plugin->getId())) {
                if ($row = $dar->getRow()) {
                    $priority = (int)$row['priority'];
                }
            }
        }
        return $priority;
    }
    
    function setPriorityForPluginHook($plugin, $hook, $priority) {
        $priority_dao = $this->_getPriorityPluginHookDao();
        return $priority_dao->setPriorityForHook_PluginId($hook, $plugin->getId(), $priority);
    }
    
    function _getPriorityPluginHookDao() {
        if (!is_a($this->priorityPluginHookDao, 'PriorityPluginHookDao')) {
            $this->priorityPluginHookDao = new PriorityPluginHookDao(CodendiDataAccess::instance());
        }
        return $this->priorityPluginHookDao;
    }
    
    function removePlugin($plugin) {
        $priority_dao = $this->_getPriorityPluginHookDao();
        return $priority_dao->deleteByPluginId($plugin->getId());
    }
}
?>
