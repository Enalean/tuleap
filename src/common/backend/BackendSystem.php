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
