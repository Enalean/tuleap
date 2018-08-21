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
}
