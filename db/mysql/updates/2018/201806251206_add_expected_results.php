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

class b201806251206_add_expected_results extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return "Add expected results";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_testmanagement_changeset_value_stepdef
                ADD COLUMN expected_results TEXT,
                ADD COLUMN expected_results_format VARCHAR(10) NOT NULL DEFAULT 'text'";
        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('plugin_testmanagement_changeset_value_stepdef', 'expected_results')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occured while adding issue expected_results in table columns'
            );
        }
        if (! $this->db->columnNameExists('plugin_testmanagement_changeset_value_stepdef', 'expected_results_format')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occured while adding issue expected_results_format in table columns'
            );
        }
    }
}
