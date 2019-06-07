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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

class b201905211004_add_new_version_upload_hardcoded_metadata_columns extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add item status, obsolescence date, new title, new description columns. It will be used when an user upload a new version of file';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_docman_new_version_upload ADD status TINYINT(4) DEFAULT 100 NOT NULL";
        $this->db->alterTable(
            'plugin_docman_new_version_upload',
            'tuleap',
            'status',
            $sql
        );

        $sql = "ALTER TABLE plugin_docman_new_version_upload ADD obsolescence_date int(11) DEFAULT 0 NOT NULL";
        $this->db->alterTable(
            'plugin_docman_new_version_upload',
            'tuleap',
            'obsolescence_date',
            $sql
        );

        $sql = "ALTER TABLE plugin_docman_new_version_upload ADD title varchar(255) NULL";
        $this->db->alterTable(
            'plugin_docman_new_version_upload',
            'tuleap',
            'new_title',
            $sql
        );

        $sql = "ALTER TABLE plugin_docman_new_version_upload ADD description text NULL";
        $this->db->alterTable(
            'plugin_docman_new_version_upload',
            'tuleap',
            'new_description',
            $sql
        );
    }
}
