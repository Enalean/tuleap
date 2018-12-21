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
 *
 */

class b201812212041_change_display_plateform_statistics_news_options extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add statistics and news option in homepage';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->alterTable(
            'homepage',
            'tuleap',
            'display_platform_statistics',
            'ALTER TABLE homepage DROP COLUMN display_platform_statistics'
        );

        $sql = 'REPLACE INTO forgeconfig (name, value) VALUES ("display_homepage_statistics", "1")';
        $this->db->dbh->exec($sql);

        $sql = 'REPLACE INTO forgeconfig (name, value) VALUES ("display_homepage_news", "1")';
        $this->db->dbh->exec($sql);
    }
}
