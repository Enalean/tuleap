<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

interface PFO_Plugin
{

    /**
     * Listen to an event and associate a callback
     *
     * @param String  $hook       Event name
     * @param String  $callback   Callback method name (method of the current object)
     * @param bool $recallHook If set to true, event name is passed as parameter
     *
     * @return void
     */
    public function addHook($hook, $callback = null, $recallHook = false);
}
