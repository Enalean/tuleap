<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 *
 */


class BackendSystem extends Backend
{
    public function systemCheck(\Project $project): void
    {
        // Recreate project directories if they were deleted
        if (! $this->createProjectFRSDirectory($project)) {
            throw new \RuntimeException('Could not create project FRS directory');
        }
    }

    public function createProjectFRSDirectory(Project $project): bool
    {
        $unix_group_name = $project->getUnixNameMixedCase();
        $ftp_frs_dir     = ForgeConfig::get('ftp_frs_dir_prefix') . "/" . $unix_group_name;

        if (is_dir(ForgeConfig::get('ftp_frs_dir_prefix'))) {
            if (! is_dir($ftp_frs_dir)) {
                // Now lets create the group's ftp homedir for anonymous ftp space
                // This one must be owned by the project gid so that all project
                // admins can work on it (upload, delete, etc...)
                if (mkdir($ftp_frs_dir, 0771)) {
                    chmod($ftp_frs_dir, 0771);
                    $this->chown($ftp_frs_dir, "dummy");
                    $this->chgrp($ftp_frs_dir, $this->getUnixGroupNameForProject($project));
                } else {
                    $this->log("Can't create project file release dir: $ftp_frs_dir", Backend::LOG_ERROR);
                    return false;
                }
            }
        } else {
            $this->log("Skip create project file release dir: $ftp_frs_dir", Backend::LOG_INFO);
        }
        return true;
    }

    /**
     * Remove deleted releases and released files
     *
     * @return bool the status
     */
    public function cleanupFRS()
    {
        $status = true;
        // Purge all deleted files older than 3 days old
        $delay = (int) ForgeConfig::get('sys_file_deletion_delay', 3);
        $time  = $_SERVER['REQUEST_TIME'] - (3600 * 24 * $delay);

        if (is_dir(ForgeConfig::get('ftp_frs_dir_prefix'))) {
            $frs    = $this->getFRSFileFactory();
            $status =  $frs->moveFiles($time, $this);
        }

        if (is_dir(ForgeConfig::get('sys_wiki_attachment_data_dir'))) {
            $wiki   = $this->getWikiAttachment();
            $status = $status && $wiki->purgeAttachments($time);
        }

        $em = EventManager::instance();
        $em->processEvent('backend_system_purge_files', ['time' => $time]);

        return $status;
    }

    /**
     * Rename Directory where the released files are located (following project unix_name change)
     *
     * @param Project $project a project
     * @param String  $newName a new name
     *
     * @return bool
     */
    public function renameFileReleasedDirectory($project, $newName)
    {
        $ftp_frs_dir_prefix = ForgeConfig::get('ftp_frs_dir_prefix');
        $project_old_name   = $project->getUnixName(false);

        if (
            is_dir($ftp_frs_dir_prefix . '/' . $project_old_name) &&
            ! rename($ftp_frs_dir_prefix . '/' . $project_old_name, $ftp_frs_dir_prefix . '/' . $newName)
        ) {
            return false;
        }

        if (
            is_dir($ftp_frs_dir_prefix . '/DELETED/' . $project_old_name) &&
            ! rename($ftp_frs_dir_prefix . '/DELETED/' . $project_old_name, $ftp_frs_dir_prefix . '/DELETED/' . $newName)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Wrapper for getFRSFileFactory
     *
     * @return FRSFileFactory
     */
    protected function getFRSFileFactory()
    {
        return new FRSFileFactory();
    }

    /**
     * Wrapper for getWikiAttachment
     *
     * @return WikiAttachment
     */
    protected function getWikiAttachment()
    {
        return new WikiAttachment();
    }
}
