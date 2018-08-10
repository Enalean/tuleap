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
use Tuleap\Request\ForbiddenException;
use Tuleap\TuleapSynchro\ListEndpoints\ListEndpointsController;

class EndpointCreatorController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var ListEndpointsController
     */
    private $list_tlp_end_points_controller;

    /**
     * @var EndpointChecker
     */
    private $checker;

    public function __construct(ListEndpointsController $list_tlp_end_points_controller, EndpointChecker $endpoint_checker)
    {
        $this->list_tlp_end_points_controller = $list_tlp_end_points_controller;
        $this->checker                        = $endpoint_checker;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     * @throws ForbiddenException
     * @throws \Tuleap\TuleapSynchro\Exception\TrackerIdIsNotValidException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $this->checker->checkData($request->params);
        $this->list_tlp_end_points_controller->process($request, $layout, $variables);
    }
}
