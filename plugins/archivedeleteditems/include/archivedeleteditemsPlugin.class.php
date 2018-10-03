<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics 2012. All rights reserved
 *
 * This file is a part of Tuleap.
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

require_once __DIR__ . '/../vendor/autoload.php';

use Tuleap\ArchiveDeletedItems\ArchiveLogger;
use Tuleap\ArchiveDeletedItems\FileCopier;

/**
 * Archive
 */
class ArchivedeleteditemsPlugin extends Plugin
{
    /**
     * Constructor of the class
     *
     * @param Integer $id Id of the plugin
     *
     * @return Void
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_SYSTEM);
        $this->addHook('archive_deleted_item', 'archive_deleted_item', false);
    }

    private function getLogger()
    {
        return new ArchiveLogger();
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
    public function archive_deleted_item($params)
    {
        $logger           = $this->getLogger();
        $params['status'] = false;

        if (!empty($params['source_path'])) {
            $source_path = $params['source_path'];
        } else {
            $params['error'] = 'Missing argument source path';
            return;
        }

        $archive_path = $this->getWellFormattedArchivePath();

        if (! empty($archive_path)) {
            if (!is_dir($archive_path)) {
                $params['error'] = 'Non-existing archive path';
                return;
            }
        } else {
            $params['error'] = 'Missing argument archive path';
            return;
        }

        if (! empty($params['archive_prefix'])) {
            $archive_prefix = $params['archive_prefix'];
        } else {
            $params['error'] = 'Missing argument archive prefix';
            return;
        }

        $destination_path = $archive_path.$archive_prefix.'_'.basename($source_path);

        if (! file_exists($source_path)) {
            $params['error'] = 'Skipping file "'.$source_path.'": not found in file system.';
            $logger->error($params['error']);
            return;
        }

        $skip_duplicated    = $params['skip_duplicated'];
        $file_copier        = new FileCopier($logger);
        $is_copy_successful = $file_copier->copy($source_path, $destination_path, $skip_duplicated);

        if ($is_copy_successful) {
            $logger->info('Archiving OK');
        } else {
            $params['error'] = 'Archiving of "' . $source_path . '" in "' . $destination_path . '" failed';
        }

        $params['status'] = $is_copy_successful;
    }

    private function getWellFormattedArchivePath() {
        $archive_path = $this->getConfigurationParameter('archive_path');

        if ($archive_path) {
            $archive_path  = rtrim($archive_path, '/');
            $archive_path .= '/';
        }

        return $archive_path;
    }
}
