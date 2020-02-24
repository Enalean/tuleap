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

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class EndpointDeleteController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var EndpointUpdater
     */
    private $endpoint_updater;

    public function __construct(EndpointUpdater $endpoint_updater)
    {
        $this->endpoint_updater    = $endpoint_updater;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @return void
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $csrf_token_delete = new CSRFSynchronizerToken(TULEAP_SYNCHRO_URL . '/delete_endpoint');
        $csrf_token_delete->check();

        $this->endpoint_updater->deleteEndpoint($request->params);
        $layout->redirect(TULEAP_SYNCHRO_URL);
    }
}
