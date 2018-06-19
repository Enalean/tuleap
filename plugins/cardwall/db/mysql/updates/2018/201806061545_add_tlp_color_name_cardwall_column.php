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


class b201806061545_add_tlp_color_name_cardwall_column extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add column tlp_color_name in table plugin_cardwall_on_top_column';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (! $this->db->columnNameExists('plugin_cardwall_on_top_column', 'tlp_color_name')) {
            $sql = 'ALTER TABLE plugin_cardwall_on_top_column ADD COLUMN tlp_color_name VARCHAR (30) NULL';

            $this->db->dbh->exec($sql);

            if (! $this->db->columnNameExists('plugin_cardwall_on_top_column', 'tlp_color_name')) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_cardwall_on_top_column update failed');
            }
        }
    }
}
