<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class b201804171521_fill_execution_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Fill plugin_testmanagement_execution table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "REPLACE INTO plugin_testmanagement_execution (execution_artifact_id, definition_changeset_id)
                SELECT
                    exec.id,
                    def.last_changeset_id
                FROM plugin_testmanagement AS config
                    INNER JOIN tracker_artifact AS exec ON (config.test_execution_tracker_id = exec.tracker_id)
                    INNER JOIN tracker_artifact AS def ON (config.test_definition_tracker_id = def.tracker_id)
                    INNER JOIN tracker_field AS field
                        ON (config.test_execution_tracker_id = field.tracker_id AND field.formElement_type = 'art_link')
                    INNER JOIN tracker_changeset_value AS CV
                        ON (CV.changeset_id = exec.last_changeset_id AND CV.field_id = field.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS CVAL
                        ON (CVAL.changeset_value_id = CV.id AND CVAL.artifact_id = def.id)";

        $this->db->dbh->exec($sql);
    }
}
