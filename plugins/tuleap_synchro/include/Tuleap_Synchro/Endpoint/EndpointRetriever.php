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

namespace Tuleap\TuleapSynchro\Endpoint;

use Tuleap\TuleapSynchro\Dao\TuleapSynchroDao;

class EndpointRetriever
{
    /**
     * @var TuleapSynchroDao
     */
    private $endpoint_dao;
    private $endpoint_builder;

    public function __construct(TuleapSynchroDao $endpoint_dao, EndpointBuilder $endpoint_builder)
    {
        $this->endpoint_dao     = $endpoint_dao;
        $this->endpoint_builder = $endpoint_builder;
    }


    /**
     * @param $webhook
     * @return null|Endpoint
     */
    public function getEndpoint($webhook)
    {
        $row_endpoint = $this->endpoint_dao->getEndpoint($webhook);

        if ($row_endpoint === null) {
            return null;
        }

        return $this->buildEndpointFromRow($row_endpoint);
    }

    /**
     * @param array $row
     * @return Endpoint
     */
    private function buildEndpointFromRow(array $row)
    {
        return $this->endpoint_builder->build(
            $row['username_source'],
            $row['password_source'],
            $row['project_source'],
            $row['tracker_source'],
            $row['project_target'],
            $row['username_target'],
            $row['base_uri'],
            $row['webhook']
        );
    }
}
