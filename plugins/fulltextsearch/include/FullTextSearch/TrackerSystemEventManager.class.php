<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class FullTextSearch_TrackerSystemEventManager {
    /**
     * @var fulltextsearchPlugin
     */
    private $plugin;

    /**
     * @var ElasticSearch_IndexClientFacade
     */
    private $actions;

    /**
     * @var SystemEventManager
     */
    private $system_event_manager;

    public function __construct(
            SystemEventManager $system_event_manager,
            FullTextSearchTrackerActions $actions,
            fulltextsearchPlugin $plugin
            ) {
        $this->system_event_manager = $system_event_manager;
        $this->actions              = $actions;
        $this->plugin               = $plugin;
    }

    public function getSystemEventClass($type, &$class, &$dependencies) {
        switch($type) {
            case SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP_ADD::NAME:
            case SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP_UPDATE::NAME:
                $class        = 'SystemEvent_'. $type;
                $dependencies = array($this->actions);
                break;
        }
    }

    public function queueAddFollowup($group_id, $artifact_id, $changeset_id, $text) {
        if ($this->plugin->isAllowed($group_id)) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP_ADD::NAME,
                $this->implodeParams(array($group_id, $artifact_id, $changeset_id, $text)),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    public function queueUpdateFollowup($group_id, $artifact_id, $changeset_id, $text) {
        if ($this->plugin->isAllowed($group_id)) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP_UPDATE::NAME,
                $this->implodeParams(array($group_id, $artifact_id, $changeset_id, $text)),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    private function implodeParams(array $params) {
        return implode(SystemEvent::PARAMETER_SEPARATOR, $params);
    }
}
