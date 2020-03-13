<?php
/**
* Copyright Enalean (c) 2015. All rights reserved.
*
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

class Statistics_ConfigurationDao extends DataAccessObject
{

    public function isDailyPurgeActivated()
    {
        $sql = "SELECT daily_purge_is_activated FROM plugin_statistics_configuration";

        $row = $this->retrieve($sql)->getRow();

        return (bool) $row['daily_purge_is_activated'];
    }

    public function activateDailyPurge()
    {
        $this->resetDailyPurgeConfiguration();

        $sql = "REPLACE INTO plugin_statistics_configuration (daily_purge_is_activated) VALUES (1)";

        return $this->update($sql);
    }

    private function resetDailyPurgeConfiguration()
    {
        $sql = "TRUNCATE TABLE plugin_statistics_configuration";

        return $this->update($sql);
    }
}
