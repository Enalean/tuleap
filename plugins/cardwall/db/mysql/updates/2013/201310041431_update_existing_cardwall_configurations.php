<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

class b201310041431_update_existing_cardwall_configurations extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Creating table plugin_agiledashboard_semantic_initial_effort.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        // clean-out existing unused cardwall custom columns
        $sql = "DELETE FROM plugin_cardwall_on_top_column
                USING plugin_cardwall_on_top JOIN plugin_cardwall_on_top_column
                WHERE plugin_cardwall_on_top.use_freestyle_columns = 0
                AND   plugin_cardwall_on_top.tracker_id = plugin_cardwall_on_top_column.tracker_id";
        $res = $this->db->dbh->exec($sql);

        //create new columns
        $sql = "INSERT INTO plugin_cardwall_on_top_column (tracker_id, `label`, bg_red, bg_green, bg_blue)
                SELECT DISTINCT plugin_cardwall_on_top.tracker_id, tracker_field_list_bind_static_value.label, deco.red, deco.green, deco.blue
                FROM plugin_cardwall_on_top
                    JOIN plugin_agiledashboard_planning
                        ON plugin_agiledashboard_planning.planning_tracker_id = plugin_cardwall_on_top.tracker_id
                    JOIN plugin_agiledashboard_planning_backlog_tracker
                        ON plugin_agiledashboard_planning_backlog_tracker.planning_id = plugin_agiledashboard_planning.id
                    JOIN tracker_semantic_status
                        ON plugin_agiledashboard_planning_backlog_tracker.tracker_id = tracker_semantic_status.tracker_id
                    JOIN tracker_field_list_bind_static_value
                        ON tracker_semantic_status.field_id = tracker_field_list_bind_static_value.field_id
                    JOIN tracker_field
                        ON (tracker_field.id = tracker_semantic_status.field_id AND tracker_field.use_it = 1)
                    LEFT JOIN tracker_field_list_bind_decorator deco
                        ON deco.value_id = tracker_field_list_bind_static_value.id
                WHERE plugin_cardwall_on_top.use_freestyle_columns = 0";

        $res = $this->db->dbh->exec($sql);

        //update values to use new columns
        $sql = "UPDATE plugin_cardwall_on_top_column_mapping_field_value AS value
                JOIN tracker_field_list_bind_static_value
                    ON value.column_id  = tracker_field_list_bind_static_value.id
                JOIN plugin_cardwall_on_top_column AS cardwall_column
                    ON cardwall_column.label = tracker_field_list_bind_static_value.label
                       AND
                       cardwall_column.tracker_id = value.cardwall_tracker_id
                JOIN plugin_cardwall_on_top
                    ON plugin_cardwall_on_top.tracker_id = cardwall_column.tracker_id
                SET value.column_id = cardwall_column.id
                WHERE plugin_cardwall_on_top.use_freestyle_columns = 0";
        $res = $this->db->dbh->exec($sql);

        //set all carwalls to use custom columns
        $sql = "UPDATE plugin_cardwall_on_top
                SET plugin_cardwall_on_top.use_freestyle_columns = 1";
        $res = $this->db->dbh->exec($sql);
    }
}
