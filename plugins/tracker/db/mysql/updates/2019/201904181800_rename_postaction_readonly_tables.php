<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class b201904181800_rename_postaction_readonly_tables extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Rename plugin_tracker_workflow_transition_postactions_read_only and plugin_tracker_workflow_transition_postactions_read_only_fields tables to plugin_tracker_workflow_transition_postactions_frozen_fields and plugin_tracker_workflow_transition_postactions_frozen_fields_value';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->renamePostActionTable();
        $this->renameFieldsTable();
    }

    private function renamePostActionTable(): void
    {
        $sql         = "RENAME TABLE plugin_tracker_workflow_transition_postactions_read_only TO plugin_tracker_workflow_postactions_frozen_fields";
        $exec_result = $this->db->dbh->exec($sql);

        if ($exec_result === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Failed renaming plugin_tracker_workflow_transition_postactions_read_only to plugin_tracker_workflow_postactions_frozen_fields');
        }
    }

    private function renameFieldsTable(): void
    {
        $sql         = "RENAME TABLE plugin_tracker_workflow_transition_postactions_read_only_fields TO plugin_tracker_workflow_postactions_frozen_fields_value";
        $exec_result = $this->db->dbh->exec($sql);

        if ($exec_result === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Failed renaming plugin_tracker_workflow_transition_postactions_read_only_fields to plugin_tracker_workflow_postactions_frozen_fields_value');
        }
    }
}
