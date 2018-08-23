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

class Endpoint
{
    private $username_source;
    private $username_target;
    private $project_target;
    private $webhook;
    private $base_uri;
    private $project_source;
    private $tracker_source;

    public function __construct($username_source, $project_source, $tracker_source, $username_target, $project_target, $base_uri, $webhook)
    {
        $this->username_source = $username_source;
        $this->project_source  = $project_source;
        $this->tracker_source  = $tracker_source;
        $this->username_target = $username_target;
        $this->project_target  = $project_target;
        $this->base_uri        = $base_uri;
        $this->webhook         = $webhook;
    }

    public function getUsernameSource()
    {
        return $this->username_source;
    }

    public function getUsernameTarget()
    {
        return $this->username_target;
    }

    public function getProjectTarget()
    {
        return $this->project_target;
    }

    public function getWebhook()
    {
        return $this->webhook;
    }

    public function getBaseUri()
    {
        return $this->base_uri;
    }

    public function getProjectSource()
    {
        return $this->project_source;
    }

    public function getTrackerSource()
    {
        return $this->tracker_source;
    }
}
