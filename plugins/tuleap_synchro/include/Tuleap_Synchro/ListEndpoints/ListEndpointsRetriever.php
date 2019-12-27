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

namespace Tuleap\TuleapSynchro\ListEndpoints;

use Tuleap\TuleapSynchro\Dao\TuleapSynchroDao;
use Tuleap\TuleapSynchro\Endpoint\Endpoint;
use Tuleap\TuleapSynchro\Endpoint\EndpointBuilder;

class ListEndpointsRetriever
{
    /**
     * @var TuleapSynchroDao
     */
    private $tuleap_synchro_dao;

    /**
     * @var EndpointBuilder
     */
    private $endpoint_builder;

    public function __construct(TuleapSynchroDao $endpoint_dao, EndpointBuilder $endpoint_builder)
    {
        $this->tuleap_synchro_dao = $endpoint_dao;
        $this->endpoint_builder   = $endpoint_builder;
    }

    /**
     * @return Endpoint[]
     */
    public function getAllEndpoints()
    {
        $row_endpoints = $this->tuleap_synchro_dao->getAllEndpoints();
        $list_endpoint = [];

        if ($row_endpoints === null) {
            return $list_endpoint;
        }

        foreach ($row_endpoints as $row_endpoint) {
            $list_endpoint[] = $this->endpoint_builder->build(
                $row_endpoint['username_source'],
                $row_endpoint['password_source'],
                $row_endpoint['project_source'],
                $row_endpoint['tracker_source'],
                $row_endpoint['username_target'],
                $row_endpoint['project_target'],
                $row_endpoint['base_uri'],
                $row_endpoint['webhook']
            );
        }

        return $list_endpoint;
    }
}
