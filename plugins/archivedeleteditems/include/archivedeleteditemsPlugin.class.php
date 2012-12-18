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

/**
 * Archive
 */
class ArchivedeleteditemsPlugin extends Plugin {

    private $archiveScript;

    /**
     * Constructor of the class
     *
     * @param Integer $id Id of the plugin
     *
     * @return Void
     */
    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_SYSTEM);
        $this->archiveScript = $GLOBALS['codendi_bin_prefix'] . "/archive-deleted-items.pl";
        $this->_addHook('archive_deleted_item', 'archive_deleted_item', false);
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
     * Copy files to the archiving directory
     *
     * @param Array $params Hook parameters
     *
     * @return Boolean
     */
    public function archive_deleted_item($params) {
        $params['status'] = false;
        if (!empty($params['source_path'])) {
            $sourcePath = $params['source_path'];
        } else {
            $params['error'] = 'Missing argument source path';
            return false;
        }

        $archivePath = $this->getConfigurationParameter('archive_path');
        if (!empty($archivePath)) {
            if(!is_dir($archivePath)) {
                $params['error'] = 'Non-existing archive path';
                return false;
            }
        } else {
            $params['error'] = 'Missing argument archive path';
            return false;
        }

        if (!empty($params['archive_prefix'])) {
            $archivePrefix = $params['archive_prefix'];
        } else {
            $params['error'] = 'Missing argument archive prefix';
            return false;
        }

        $ret_val         = null;
        $exec_res        = null;
        $destinationPath = $archivePath.$archivePrefix.'_'.basename($sourcePath);
        $cmd             = $this->archiveScript." ".$sourcePath." " .$destinationPath;
        exec($cmd, $exec_res, $ret_val);

        if ($ret_val == 0) {
            $params['status'] = true;
            return true;
        } else {
            $params['error'] = 'Archiving of "'.$sourcePath.'" in "'.$destinationPath.'" failed';
            return false;
        }
    }

}

?>