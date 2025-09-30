<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

class b201902211515_insert_default_docman_settings_values extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Insert default docman settings values.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->insertDefaultNumberOfFilesUploadableInParallelPerUser();
        $this->insertDefaultMaxFileSizeAcceptableForUpload();
    }

    private function insertDefaultNumberOfFilesUploadableInParallelPerUser()
    {
        $sql = "INSERT INTO forgeconfig VALUES ('max_number_of_files', 50)";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            throw new RuntimeException('Insertion of max_number_of_files failed');
        }
    }

    private function insertDefaultMaxFileSizeAcceptableForUpload()
    {
        $sql = "INSERT INTO forgeconfig VALUES ('max_file_size', 67108864)";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            throw new RuntimeException('Insertion of max_file_size failed');
        }
    }
}
