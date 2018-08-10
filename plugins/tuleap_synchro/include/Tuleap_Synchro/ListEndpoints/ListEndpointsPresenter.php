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

use Tuleap\TuleapSynchro\Endpoint\Endpoint;

class ListEndpointsPresenter
{
    public $username_source;
    public $project_source;
    public $tracker_source;
    public $username_target;
    public $project_target;
    public $base_uri;
    public $webhook;

    public function __construct(Endpoint $endpoint)
    {
        $this->username_source = $endpoint->getUsernameSource();
        $this->project_source  = $endpoint->getProjectSource();
        $this->tracker_source  = $endpoint->getTrackerSource();
        $this->username_target = $endpoint->getUsernameTarget();
        $this->project_target  = $endpoint->getProjectTarget();
        $this->base_uri        = $endpoint->getBaseUri();
        $this->webhook         = $endpoint->getWebhook();
    }
}
