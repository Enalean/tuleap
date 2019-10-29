<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

class b201910291413_add_tables_for_frs_download_agreement extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description(): string
    {
        return 'Add tables for frs download agreement';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = "CREATE TABLE frs_download_agreement (
            id INT(11) NOT NULL AUTO_INCREMENT,
            project_id int(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            PRIMARY KEY  (id),
            INDEX idx_project_id(project_id, id)
        )";
        $this->db->createTable('frs_download_agreement', $sql);

        $sql = "CREATE TABLE frs_package_download_agreement (
            package_id INT(11) NOT NULL,
            agreement_id INT(11) NOT NULL,
            PRIMARY KEY (package_id, agreement_id),
            INDEX idx_reverse(agreement_id, package_id)
        )";
        $this->db->createTable('frs_package_download_agreement', $sql);
    }
}
