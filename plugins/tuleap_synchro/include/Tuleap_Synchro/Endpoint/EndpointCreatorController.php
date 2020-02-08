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

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class EndpointCreatorController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var EndpointUpdater
     */
    private $endpoint_updater;

    /**
     * @var EndpointChecker
     */
    private $checker;

    public function __construct(EndpointChecker $endpoint_checker, EndpointUpdater $endpoint_updater)
    {
        $this->checker                        = $endpoint_checker;
        $this->endpoint_updater               = $endpoint_updater;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws \Tuleap\TuleapSynchro\Exception\TrackerIdIsNotValidException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $csrf_token_add = new \CSRFSynchronizerToken(TULEAP_SYNCHRO_URL . '/add_endpoint');
        $csrf_token_add->check();

        $this->checker->checkData($request->params);
        $this->endpoint_updater->addEndpoint($request->params);
        $layout->redirect(TULEAP_SYNCHRO_URL);
    }
}
