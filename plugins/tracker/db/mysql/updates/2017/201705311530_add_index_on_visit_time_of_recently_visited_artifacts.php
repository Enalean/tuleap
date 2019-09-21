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
class b201705311530_add_index_on_visit_time_of_recently_visited_artifacts extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Add index on the visit time of recently visited artifacts';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'ALTER TABLE plugin_tracker_recently_visited ADD INDEX idx_user_visit_time(user_id, created_on)';

        $this->db->addIndex('plugin_tracker_recently_visited', 'idx_user_visit_time', $sql);
    }

    public function postUp()
    {
        $sql = 'SHOW INDEX FROM plugin_tracker_recently_visited WHERE Key_name LIKE "idx_user_visit_time"';
        $res = $this->db->dbh->query($sql);
        return $res && $res->fetch() !== false;
    }
}
