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

class b201801181115_remove_invalid_tuleap_timezones extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Remove invalid timezones that were possible in Tuleap';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->migrateAsiaRiyadhTimezones();
        $this->migrateFactoryTimezone();
        $this->migrateCanadaEastSaskatchewanTimezone();
    }

    private function migrateAsiaRiyadhTimezones()
    {
        $sql = 'UPDATE user SET timezone="Asia/Riyadh" WHERE timezone LIKE "%Riyadh%"';

        if (! $this->db->dbh->query($sql)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'The migration of Asia/Riyadh and Mideast/Riyadh timezones has failed'
            );
        }
    }

    private function migrateFactoryTimezone()
    {
        $sql = 'UPDATE user SET timezone="GMT" WHERE timezone = "Factory"';

        if (! $this->db->dbh->query($sql)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'The migration of the Factory timezone to GMT timezone has failed'
            );
        }
    }

    private function migrateCanadaEastSaskatchewanTimezone()
    {
        $sql = 'UPDATE user SET timezone="America/Regina" WHERE timezone = "Canada/East-Saskatchewan"';

        if (! $this->db->dbh->query($sql)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'The migration of the Canada/East-Saskatchewan timezone to America/Regina timezone has failed'
            );
        }
    }
}
