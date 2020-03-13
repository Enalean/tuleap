<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


class b201302271529_add_cumulative_flow_chart_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add new table plugin_graphontrackersv5_cumulative_flow_chart to manage a new type of chart.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (!$this->db->tableNameExists('plugin_graphontrackersv5_cumulative_flow_chart')) {
            $sql = "CREATE TABLE plugin_graphontrackersv5_cumulative_flow_chart(
  id int(11)  NOT NULL PRIMARY KEY ,
  field_id int(11),
  start_date int(11),
  stop_date int(11),
  scale tinyint(1)
)";
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding table plugin_graphontrackersv5_cumulative_flow_chart: ' . implode(', ', $this->db->dbh->errorInfo()));
            }
        }
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('plugin_graphontrackersv5_cumulative_flow_chart')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('An error occured while adding table plugin_graphontrackersv5_cumulative_flow_chart');
        }
    }
}
