<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201504171343_restore_FRS_owner_group extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Restore FRS owner group";
    }

    public function preUp()
    {
        $this->needed_group_name = 'codendiadm';

        $codendi_ugroup = posix_getgrnam($this->needed_group_name);

        $this->codendi_ugroup_id   = $codendi_ugroup['gid'];
        $this->needed_access_right = '0755';
    }

    public function up()
    {
        include('/etc/codendi/conf/local.inc');
        $project_dirs = new DirectoryIterator($ftp_frs_dir_prefix);

        foreach ($project_dirs as $project_dir) {
            if (! $project_dir->isDot() && $project_dir->isDir()) {
                echo 'Checking in ' . basename($project_dir->getPathname()) . PHP_EOL;

                $frs_project_dirs = new DirectoryIterator($project_dir->getPathname());
                $this->checkInProjectFolder($frs_project_dirs);
            }
        }
    }

    private function checkInProjectFolder(DirectoryIterator $frs_project_dirs)
    {
        foreach ($frs_project_dirs as $frs_project_dir) {
            if ($this->folderNeedsToBeUpdated($frs_project_dir)) {
                echo $frs_project_dir->getPathname() . ' needs to be updated' . PHP_EOL;

                chgrp($frs_project_dir->getPathname(), $this->needed_group_name);
            }
        }
    }

    private function folderNeedsToBeUpdated(DirectoryIterator $frs_project_dir)
    {
        $comparable_folder_rights = $this->getComprarableFolderRights(
            $frs_project_dir->getPerms()
        );

        return ! $frs_project_dir->isDot() &&
               $frs_project_dir->isDir() &&
               $comparable_folder_rights != $this->needed_access_right &&
               $frs_project_dir->getGroup() != $this->codendi_ugroup_id;
    }

    private function getComprarableFolderRights($rights)
    {
        return substr(sprintf('%o', $rights), -4);
    }
}
