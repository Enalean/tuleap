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

namespace Tuleap\Request;

class RestrictedUsersAreHandledByPluginEvent implements \Tuleap\Event\Dispatchable
{
    const NAME = 'restrictedUsersAreHandledByPluginEvent';

    private $uri;
    private $plugin_handle_restricted = false;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    public function setPluginHandleRestricted()
    {
        $this->plugin_handle_restricted = true;
    }

    /**
     * @return bool
     */
    public function getPluginHandleRestricted()
    {
        return $this->plugin_handle_restricted;
    }
}
