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
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

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
            Tracker_ArtifactFactory $artifact_factory,
            TrackerFactory $tracker_factory,
            fulltextsearchPlugin $plugin
            ) {
        $this->system_event_manager = $system_event_manager;
        $this->actions              = $actions;
        $this->artifact_factory     = $artifact_factory;
        $this->tracker_factory      = $tracker_factory;
        $this->plugin               = $plugin;
    }

    public function getSystemEventClass($type, &$class, &$dependencies) {
        switch($type) {
            case SystemEvent_FULLTEXTSEARCH_TRACKER_ARTIFACT_UPDATE::NAME:
                $class        = 'SystemEvent_'. $type;
                $dependencies = array($this->actions, $this->artifact_factory);
                break;
            case SystemEvent_FULLTEXTSEARCH_TRACKER_REINDEX_PROJECT::NAME:
                $class        = 'SystemEvent_'. $type;
                $dependencies = array($this->actions, $this->tracker_factory);
                break;
            case SystemEvent_FULLTEXTSEARCH_TRACKER_ARTIFACT_DELETE::NAME:
                $class        = 'SystemEvent_'. $type;
                $dependencies = array($this->actions);
                break;
            case SystemEvent_FULLTEXTSEARCH_TRACKER_TRACKER_DELETE::NAME:
                $class        = 'SystemEvent_'. $type;
                $dependencies = array($this->actions);
                break;
        }
    }

    public function queueArtifactUpdate(Tracker_Artifact $artifact) {
        if ($this->plugin->isAllowed($artifact->getTracker()->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_TRACKER_ARTIFACT_UPDATE::NAME,
                $this->implodeParams(array($artifact->getId())),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueTrackersProjectReindexation($project_id) {
        if ($this->plugin->isAllowed($project_id)) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_TRACKER_REINDEX_PROJECT::NAME,
                $project_id,
                SystemEvent::PRIORITY_LOW,
                SystemEvent::OWNER_APP
            );
        }
    }

    private function implodeParams(array $params) {
        return implode(SystemEvent::PARAMETER_SEPARATOR, $params);
    }

    public function queueArtifactDelete(Tracker_Artifact $artifact) {
        if ($this->plugin->isAllowed($artifact->getTracker()->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_TRACKER_ARTIFACT_DELETE::NAME,
                $this->implodeParams(array($artifact->getId(), $artifact->getTrackerId())),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueTrackerDelete(Tracker $tracker) {
        if ($this->plugin->isAllowed($tracker->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_TRACKER_TRACKER_DELETE::NAME,
                $this->implodeParams(array($tracker->getId())),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }
}
