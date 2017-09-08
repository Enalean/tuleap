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

class b201709081530_rename_plugin_structure_to_test_management extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Rename plugin structure to Test Management';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->updatePlugin();
        $this->updateServiceLinks();
    }

    private function updatePlugin()
    {
        $sql = "UPDATE plugin SET name = 'testmanagement' WHERE name = 'trafficlights'";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while updating plugin');
        }
    }

    private function updateServiceLinks()
    {
        $sql = "UPDATE service SET link = REPLACE(link, '/plugins/trafficlights', '/plugins/testmanagement') WHERE short_name = 'plugin_testmanagement'";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while service links');
        }
    }
}
