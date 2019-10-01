<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class b201710201508_add_project_index_on_unused_artifact_link_types extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Add project index on the unused artifact link types table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'ALTER TABLE plugin_tracker_projects_unused_artifactlink_types
                ADD INDEX idx_artifactlink_types_unused_project_id(project_id)';

        $this->db->addIndex(
            'plugin_tracker_projects_unused_artifactlink_types',
            'idx_artifactlink_types_unused_project_id',
            $sql
        );
    }

    public function postUp()
    {
        $sql = 'SHOW INDEX FROM plugin_tracker_projects_unused_artifactlink_types
                WHERE Key_name LIKE "idx_artifactlink_types_unused_project_id"';

        $res = $this->db->dbh->query($sql);
        return $res && $res->fetch() !== false;
    }
}
