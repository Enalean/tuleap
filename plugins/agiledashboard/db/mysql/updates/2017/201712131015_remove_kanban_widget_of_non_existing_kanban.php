<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

class b201712131015_remove_kanban_widget_of_non_existing_kanban extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Remove kanban widgets of non existing kanban';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'DELETE plugin_agiledashboard_kanban_widget
                FROM plugin_agiledashboard_kanban_widget
                LEFT JOIN plugin_agiledashboard_kanban_configuration ON plugin_agiledashboard_kanban_widget.kanban_id = plugin_agiledashboard_kanban_configuration.id
                WHERE plugin_agiledashboard_kanban_configuration.id IS NULL;';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while cleaning plugin_agiledashboard_kanban_widget table of non existing kanban'
            );
        }
    }
}
