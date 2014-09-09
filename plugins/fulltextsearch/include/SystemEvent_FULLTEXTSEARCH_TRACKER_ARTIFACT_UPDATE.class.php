<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

class SystemEvent_FULLTEXTSEARCH_TRACKER_ARTIFACT_UPDATE extends SystemEvent {

    const NAME = 'FULLTEXTSEARCH_TRACKER_ARTIFACT_UPDATE';

    /**
     * @var FullTextSearchTrackerActions
     */
    private $actions;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function injectDependencies(FullTextSearchTrackerActions $actions, Tracker_ArtifactFactory $artifact_factory) {
        parent::injectDependencies();
        $this->setFullTextSearchTrackerActions($actions);
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * Set dependency
     *
     * @param FullTextSearchTrackerActions $actions Dependency
     *
     * @return SystemEvent_FULLTEXTSEARCH_TRACKER_ARTIFACT_UPDATE
     */
    public function setFullTextSearchTrackerActions(FullTextSearchTrackerActions $actions) {
        $this->actions = $actions;
        return $this;
    }

    /**
     * Process the system event
     *
     * @return Boolean
     */
    public function process() {
        try {
            $artifact_id = (int)$this->getRequiredParameter(0);

            if ($this->action($artifact_id)) {
                $this->done();
                return true;
            } else {
                $this->error('Error while performing action');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        return false;
    }

    private function action($artifact_id) {
        $this->actions->indexArtifactUpdate($this->artifact_factory->getArtifactById($artifact_id));
        return true;
    }

    /**
     * Verbalize parameters
     *
     * @param Boolean $withLink Create link for the params
     *
     * @return String
     */
    public function verbalizeParameters($withLink) {
        $txt = '';
        try {
            $artifact_id = (int)$this->getRequiredParameter(0);

            $txt = 'Artifact: '.$this->verbalizeArtifactId($artifact_id, $withLink);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $txt;
    }

    /**
     * Verbalize artifact & changeset
     *
     * @param Integer $artifact_id  Id of the artifact
     * @param Boolean $withLink    Create link for the params
     *
     * @return String
     */
    private function verbalizeArtifactId($artifact_id, $withLink) {
        $txt = '#'.$artifact_id;
        if ($withLink) {
            $txt = '<a href="/plugins/tracker/?aid='. $artifact_id .'">'. $txt .'</a>';
        }
        return $txt;
    }

}
