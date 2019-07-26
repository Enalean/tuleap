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
 */

namespace Tuleap\Docman\Log;

use Docman_Log;
use EventManager;

class LogEventAdder
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var Docman_Log
     */
    private $logger;

    public function __construct(EventManager $event_manager, Docman_Log $logger)
    {
        $this->event_manager = $event_manager;
        $this->logger = $logger;
    }

    public function addLogEventManagement()
    {
        // Events that will call the Docman Logger
        $log_events = [
            'plugin_docman_event_add',
            'plugin_docman_event_edit',
            'plugin_docman_event_move',
            'plugin_docman_event_del',
            'plugin_docman_event_del_version',
            'plugin_docman_event_access',
            'plugin_docman_event_new_version',
            'plugin_docman_event_restore',
            'plugin_docman_event_restore_version',
            'plugin_docman_event_metadata_update',
            'plugin_docman_event_set_version_author',
            'plugin_docman_event_set_version_date',
            'plugin_docman_event_perms_change',
        ];

        foreach ($log_events as $event) {
            $this->event_manager->addListener($event, $this->logger, 'log', true);
        }
    }
}
