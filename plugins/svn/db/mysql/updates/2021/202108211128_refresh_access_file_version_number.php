<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class b202108211128_refresh_access_file_version_number extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Refresh version number for access files in SVN plugins';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql_all_repositories = 'SELECT * FROM plugin_svn_repositories';

        foreach ($this->db->dbh->query($sql_all_repositories)->fetchAll() as $repository) {
            $repository_id = $repository['id'];

            $sql_all_access_files_repository = "SELECT *
                FROM plugin_svn_accessfile_history
                WHERE repository_id = $repository_id
                ORDER BY version_date";

            $version_number = 1;
            foreach ($this->db->dbh->query($sql_all_access_files_repository)->fetchAll() as $access_file) {
                $access_file_id = $access_file['id'];

                $sql_update_version = "UPDATE plugin_svn_accessfile_history
                    SET version_number = $version_number
                    WHERE id = $access_file_id";

                $result = $this->db->dbh->exec($sql_update_version);
                if ($result === false) {
                    $error_message = implode(', ', $this->db->dbh->errorInfo());
                    throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException($error_message);
                }

                $version_number++;
            }
        }
    }
}
