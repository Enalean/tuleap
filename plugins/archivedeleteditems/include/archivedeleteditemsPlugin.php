<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
use Tuleap\Event\Events\ArchiveDeletedItemEvent;

class ArchivedeleteditemsPlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * Constructor of the class
     *
     * @param int $id Id of the plugin
     *
     * @return Void
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_SYSTEM);
        $this->addHook(\Tuleap\Event\Events\ArchiveDeletedItemEvent::NAME);
        bindtextdomain('tuleap-archivedeleteditems', __DIR__ . '/../site-content');
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return new ArchiveLogger();
    }

    /**
     * Obtain ArchiveDeletedItemsPluginInfo instance
     *
     * @return ArchiveDeletedItemsPluginInfo
     */
    public function getPluginInfo()
    {
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
    public function getConfigurationParameter($key)
    {
        return $this->getPluginInfo()->getPropertyValueForName($key);
    }

    /**
     * Copy files to the archiving directory
     */
    public function archiveDeletedItem(ArchiveDeletedItemEvent $event) : void
    {
        $logger           = $this->getLogger();

        $archive_path = $this->getWellFormattedArchivePath();

        if (! empty($archive_path)) {
            if (!is_dir($archive_path)) {
                $logger->error('Non-existing archive path');
                $event->setFailure();
                return;
            }
        } else {
            $logger->error('Missing argument archive path');
            $event->setFailure();
            return;
        }

        $source_path = $event->getSourcePath();
        $destination_path = $archive_path . $event->getArchivePrefix() . '_' . basename($source_path);

        if (! file_exists($source_path)) {
            $logger->error('Skipping file "' . $source_path . '": not found in file system.');
            $event->setFailure();
            return;
        }

        $file_copier        = new FileCopier($logger);
        $is_copy_successful = $file_copier->copy($source_path, $destination_path, $event->mustSkipDuplicated());

        if ($is_copy_successful) {
            $logger->info('Archiving OK');
        } else {
            $logger->error('Archiving of "' . $source_path . '" in "' . $destination_path . '" failed');
            $event->setFailure();
        }
    }

    private function getWellFormattedArchivePath()
    {
        $archive_path = $this->getConfigurationParameter('archive_path');

        if ($archive_path) {
            $archive_path  = rtrim($archive_path, '/');
            $archive_path .= '/';
        }

        return $archive_path;
    }
}
