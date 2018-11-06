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

namespace Tuleap\Plugin;

class EventPluginCache
{
    /**
     * @var array
     */
    public $plugin_map;

    /**
     * @var array
     */
    public $event_plugin_map;

    public function __construct(array $plugin_map = [], array $event_plugin_map = [])
    {
        $this->plugin_map       = $plugin_map;
        $this->event_plugin_map = $event_plugin_map;
    }

    public static function __set_state($an_array)
    {
        return new self(
            $an_array['plugin_map'],
            $an_array['event_plugin_map']
        );
    }
}
