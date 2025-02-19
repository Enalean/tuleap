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
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\Event\Events\ArchiveDeletedItemEvent;

#[ConfigKeyCategory('Archive Deleted Items')]
class ArchivedeleteditemsPlugin extends Plugin implements PluginWithConfigKeys //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    #[ConfigKey('Archive deleted items path')]
    #[ConfigKeyString('/tmp/')]
    public const CONFIG_KEY_ARCHIVE_PATH = 'archive_deleted_items_path';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_SYSTEM);
        bindtextdomain('tuleap-archivedeleteditems', __DIR__ . '/../site-content');
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return new ArchiveLogger();
    }

    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
            $this->pluginInfo->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-archivedeleteditems', 'Archive deleted items'),
                    dgettext('tuleap-archivedeleteditems', 'This plugin will move files that should be purged (permanently deleted) in a dedicated filesystem for an external archiving (archiving process itself is not managed by this plugin).'),
                )
            );
        }
        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function archiveDeletedItem(ArchiveDeletedItemEvent $event): void
    {
        $logger = $this->getLogger();

        $archive_path = $this->getWellFormattedArchivePath();

        if (! empty($archive_path)) {
            if (! is_dir($archive_path)) {
                $logger->error('Non-existing archive path');
                $event->setFailure();
                return;
            }
        } else {
            $logger->error('Missing argument archive path');
            $event->setFailure();
            return;
        }

        $source_path      = $event->getSourcePath();
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
        $archive_path = ForgeConfig::get(self::CONFIG_KEY_ARCHIVE_PATH);

        if ($archive_path) {
            $archive_path  = rtrim($archive_path, '/');
            $archive_path .= '/';
        }

        return $archive_path;
    }

    public function getConfigKeys(\Tuleap\Config\ConfigClassProvider $event): void
    {
        $event->addConfigClass(self::class);
    }
}
