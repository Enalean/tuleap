<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Mediawiki;

use ForgeConfig;
use Project;
use System_Command;
use System_Command_CommandException;
use Tuleap\Project\XML\Export\ArchiveInterface;

class MediawikiMaintenanceWrapper
{
    /**
     * @var System_Command
     */
    private $sys_command;

    public function __construct(System_Command $sys_command)
    {
        $this->sys_command = $sys_command;
    }

    private function getMaintenanceWrapperPath()
    {
        return __DIR__ . "/../bin/mw-maintenance-wrapper.php";
    }

    public function dumpBackupFull(Project $project, $backup_path)
    {
        $project_name = escapeshellarg($project->getUnixName());
        $backup_path  = escapeshellarg($backup_path);

        $command = ForgeConfig::get('codendi_dir') . '/src/utils/php-launcher.sh ' .
            $this->getMaintenanceWrapperPath() . " $project_name dumpBackup.php --full --quiet >> $backup_path ";

        $this->sys_command->exec($command);
    }

    public function dumpUploads(
        Project $project,
        ArchiveInterface $archive,
        $temporary_dump_path_on_filesystem,
        $project_name_dir
    ) {
        $folder_picture = "files";
        $archive->addEmptyDir($folder_picture);
        $this->initTemporaryExtractPictureFolder($temporary_dump_path_on_filesystem, $folder_picture);

        $this->copyFoundImagesAndIgnoreExceptionWhenMediawikiHasNoImage(
            $project,
            $temporary_dump_path_on_filesystem,
            $project_name_dir,
            $folder_picture
        );
    }

    private function initTemporaryExtractPictureFolder($temporary_dump_path_on_filesystem, $folder_picture)
    {
        $picture_system_path = escapeshellarg($temporary_dump_path_on_filesystem . "/" . $folder_picture);
        $command             = "mkdir -p $picture_system_path";

        $this->sys_command->exec($command);
    }

    private function copyFoundImagesAndIgnoreExceptionWhenMediawikiHasNoImage(
        Project $project,
        $temporary_dump_path_on_filesystem,
        $project_name_dir,
        $folder_picture
    ) {
        $picture_system_path = escapeshellarg($temporary_dump_path_on_filesystem . "/" . $folder_picture);
        $export_folder       = escapeshellarg($project_name_dir . "/images");
        $project_name        = escapeshellarg($project->getUnixName());
        $command             = ForgeConfig::get('codendi_dir') . '/src/utils/php-launcher.sh ' .
            $this->getMaintenanceWrapperPath() .
            " $project_name  dumpUploads.php --full " .
            "| sed 's~mwstore://local-backend/local-public~$export_folder~' " .
            "| xargs cp -t $picture_system_path 2>/dev/null";

        try {
            $this->sys_command->exec($command);
        } catch (System_Command_CommandException $exception) {
            if ($exception->getCode() !== 123) {
                throw $exception;
            }
        }
    }
}
