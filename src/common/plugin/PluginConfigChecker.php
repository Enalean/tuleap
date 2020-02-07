<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class PluginConfigChecker
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    private $app_user;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger   = $logger;
        $this->app_user = ForgeConfig::get('sys_http_user');
    }

    public function checkFolder(Plugin $plugin)
    {
        $plugin_etc_root = $plugin->getPluginEtcRoot();

        try {
            $this->checkFolderOwnedByAppUser($plugin_etc_root);
        } catch (Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }
    }

    public function checkIncFile($inc_file)
    {
        try {
            $this->checkIncFileOwnedByAppUser($inc_file);
        } catch (Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }
    }

    private function checkFolderOwnedByAppUser($plugin_etc_root)
    {
        if (! is_dir($plugin_etc_root)) {
            throw new Exception("Folder $plugin_etc_root does not exist");
        }

        $folder_group = posix_getgrgid(filegroup($plugin_etc_root));
        $folder_owner = posix_getpwuid(fileowner($plugin_etc_root));

        if ($folder_group['name'] !==  $this->app_user || $folder_owner['name'] !== $this->app_user) {
            throw new Exception("The folder $plugin_etc_root must be owned by $this->app_user");
        }
    }

    private function checkIncFileOwnedByAppUser($inc_file)
    {
        if (! is_file($inc_file)) {
            throw new Exception("File $inc_file does not exist");
        }

        $file_group = posix_getgrgid(filegroup($inc_file));
        $file_owner = posix_getpwuid(fileowner($inc_file));

        if ($file_group['name'] !== $this->app_user || $file_owner['name'] !== $this->app_user) {
            throw new Exception("The file $inc_file must be owned by $this->app_user");
        }
    }
}
