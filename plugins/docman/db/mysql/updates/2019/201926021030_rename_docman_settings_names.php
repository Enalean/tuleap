<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types=1);

class b201926021030_rename_docman_settings_names extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Prefix docman settings names with "docman_plugin"';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->renameMaxFileSizeAcceptableForUploadSetting();
        $this->renameNumberOfFilesUploadableInParallelPerUserSetting();
    }

    private function renameNumberOfFilesUploadableInParallelPerUserSetting()
    {
        $sql = "UPDATE forgeconfig SET name = 'plugin_docman_max_number_of_files' WHERE name = 'max_number_of_files'";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            throw new RuntimeException('Renaming of max_number_of_files failed');
        }
    }

    private function renameMaxFileSizeAcceptableForUploadSetting()
    {
        $sql = "UPDATE forgeconfig SET name = 'plugin_docman_max_file_size' WHERE name = 'max_file_size'";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            throw new RuntimeException('Renaming of max_file_size failed');
        }
    }
}
