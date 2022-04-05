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
 *
 */

namespace Tuleap\Plugin;

/**
 * This class is meant to be serialized & rehydrated directly by VarExporter.
 * For some reasons, it seems that it doesn't play well with constructor promotion
 * when trying to add a new param.
 */
class EventPluginCache
{
    public array $plugin_map        = [];
    public array $event_plugin_map  = [];
    public array $default_variables = [];

    public function __construct(array $plugin_map = [], array $event_plugin_map = [], array $default_variables = [])
    {
        $this->plugin_map        = $plugin_map;
        $this->event_plugin_map  = $event_plugin_map;
        $this->default_variables = $default_variables;
    }
}
