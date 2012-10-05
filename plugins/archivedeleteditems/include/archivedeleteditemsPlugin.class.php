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

}

?>