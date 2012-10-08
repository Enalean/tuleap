<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

require_once('common/plugin/Plugin.class.php');
require_once('common/system_event/SystemEvent.class.php');
require_once(dirname(__FILE__).'/events/SystemEvent_ARCHIVE_DELETED_ITEMS.class.php');

/**
 * Archive
 */
class ArchivedeleteditemsPlugin extends Plugin {

    /**
     * Constructor of the class
     *
     * @param Integer $id Id of the plugin
     *
     * @return Void
     */
    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->_addHook(Event::SYSTEM_EVENT_GET_TYPES, 'systemEventGetTypes', false);
        $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass', false);

        $this->_addHook('archive_deleted_item', 'archive', false);
    }

    /**
     * Obtain ArchiveDeletedItemsPluginInfo instance
     *
     * @return ArchiveDeletedItemsPluginInfo
     */
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'ArchiveDeletedItemsPluginInfo')) {
            require_once('ArchiveDeletedItemsPluginInfo.class.php');
            $this->pluginInfo = new ArchiveDeletedItemsPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * Returns the configuration defined for given variable name
     *
     * @param String $key name of the param
     *
     * @return String
     */
    public function getConfigurationParameter($key) {
        return $this->getPluginInfo()->getPropertyValueForName($key);
    }

    /**
     * Get types of system events
     *
     * @params Array $params Hook params
     *
     * @return Void
     */
    public function systemEventGetTypes($params) {
        $params['types'][] = 'ARCHIVE_DELETED_ITEMS';
    }

    /**
     * This callback make SystemEvent manager knows about plugin System Events
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function getSystemEventClass($params) {
        if ($params['type'] == 'ARCHIVE_DELETED_ITEMS') {
            $params['class'] = 'SystemEvent_ARCHIVE_DELETED_ITEMS';
        }
    }

    /**
     * Copy files to the archiving directory
     *
     * @param Array $params Hook parameters
     *
     * @return Void
     */
    public function archive($params) {
        $archivePath = $this->getConfigurationParameter('archive_path');
        SystemEventManager::instance()->createEvent('ARCHIVE_DELETED_ITEMS', $params['source_path'].SystemEvent::PARAMETER_SEPARATOR.$archivePath, SystemEvent::PRIORITY_MEDIUM);
    }

}

?>