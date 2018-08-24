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

namespace Tuleap\TuleapSynchro\Dao;

use Tuleap\DB\DataAccessObject;

class TuleapSynchroDao extends DataAccessObject
{
    /**
     * @param $webhook
     * @return mixed
     */
    public function getEndpoint($webhook)
    {
        $sql = 'SELECT *
                FROM plugin_tuleap_synchro_endpoint
                WHERE webhook = ?';

        return $this->getDB()->run($sql, $webhook);
    }

    /**
     * @return mixed
     */
    public function getAllEndpoints()
    {
        $sql = 'SELECT *
                FROM plugin_tuleap_synchro_endpoint';

        return $this->getDB()->run($sql);
    }

    /**
     * @param $webhook
     */
    public function deleteEndpoint($webhook)
    {
        $sql = "DELETE FROM plugin_tuleap_synchro_endpoint
                WHERE webhook = ?";
        $this->getDB()->run($sql, $webhook);
    }

    /**
     * @param $username_source
     * @param $password_source
     * @param $project_source
     * @param $tracker_source
     * @param $username_target
     * @param $project_target
     * @param $base_uri
     * @param $webhook
     */
    public function addEndpoint($username_source, $password_source, $project_source, $tracker_source, $username_target, $project_target, $base_uri, $webhook)
    {
        $sql = "INSERT IGNORE plugin_tuleap_synchro_endpoint (username_source, password_source, project_source, tracker_source, username_target, project_target, base_uri, webhook)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $this->getDB()->run($sql, $username_source, $password_source, $project_source, $tracker_source, $username_target, $project_target, $base_uri, $webhook);
    }
}
