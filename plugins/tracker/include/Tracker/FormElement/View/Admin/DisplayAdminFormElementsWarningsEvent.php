<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\View\Admin;

use Response;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Tracker;

class DisplayAdminFormElementsWarningsEvent implements Dispatchable
{
    public const string NAME = 'displayAdminFormElementsWarningsEvent';

    /**
     * @var Tracker
     */
    private $tracker;
    /**
     * @var Response
     */
    private $response;

    public function __construct(Tracker $tracker, Response $response)
    {
        $this->tracker  = $tracker;
        $this->response = $response;
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
