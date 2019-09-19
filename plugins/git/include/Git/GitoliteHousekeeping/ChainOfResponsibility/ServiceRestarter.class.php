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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * I restart the service
 */
class Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceRestarter extends Git_GitoliteHousekeeping_ChainOfResponsibility_Command
{

    /** @var Git_GitoliteHousekeeping_GitoliteHousekeepingResponse */
    private $response;

    /** @var BackendService */
    private $backend_service;

    public function __construct(
        Git_GitoliteHousekeeping_GitoliteHousekeepingResponse $response,
        BackendService $backend_service
    ) {
        parent::__construct();
        $this->response         = $response;
        $this->backend_service  = $backend_service;
    }

    public function execute()
    {
        $this->response->info('Restarting service');
        $this->backend_service->start();
        $this->response->success();
    }
}
