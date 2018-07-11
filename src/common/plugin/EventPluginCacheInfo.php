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

class EventPluginCacheInfo
{

    public $plugin_id;
    public $event;
    public $callback;
    public $recall_event;

    public function __construct($plugin_id, $event, $callback, $recall_event)
    {
        $this->plugin_id    = $plugin_id;
        $this->event        = $event;
        $this->callback     = $callback;
        $this->recall_event = $recall_event;
    }

    public static function __set_state($an_array)
    {
        return new self(
            $an_array['plugin_id'],
            $an_array['event'],
            $an_array['callback'],
            $an_array['recall_event']
        );
    }
}
