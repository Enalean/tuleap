<?php
/**
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

require_once 'common/system_event/SystemEvent.class.php';

abstract class SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP extends SystemEvent {

    /**
     * @var FullTextSearchTrackerActions
     */
    protected $actions;

    /**
     * Inject dependencies
     *
     * @param FullTextSearchTrackerActions $actions Dependency
     *
     * @return Void
     */
    public function injectDependencies(FullTextSearchTrackerActions $actions) {
        parent::injectDependencies();
        $this->setFullTextSearchTrackerActions($actions);
    }

    /**
     * Set dependency
     *
     * @param FullTextSearchTrackerActions $actions Dependency
     *
     * @return SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP
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
            $groupId     = (int)$this->getRequiredParameter(0);
            $artifactId  = (int)$this->getRequiredParameter(1);
            $changesetId = (int)$this->getRequiredParameter(2);
            $text        = $this->getRequiredParameter(3);

            if ($this->action($groupId, $artifactId, $changesetId, $text)) {
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

    /**
     * Execute action
     *
     * @param Integer $groupId     Project Id
     * @param Integer $artifactId  Artifact Id
     * @param Integer $changesetId Changeset Id
     * @param String  $text        Comment body
     *
     * @return Boolean
     */
    protected abstract function action($groupId, $artifactId, $changesetId, $text);

    /**
     * Verbalize parameters
     * We display only a substring of the output of the indexed text of big comments
     * may clutter the interface in systemevent monitor console.
     *
     * @param Boolean $withLink Create link for the params
     *
     * @return String
     */
    public function verbalizeParameters($withLink) {
        $groupId     = (int)$this->getRequiredParameter(0);
        $artifactId  = (int)$this->getRequiredParameter(1);
        $changesetId = (int)$this->getRequiredParameter(2);
        $text        = $this->getRequiredParameter(3);
        if (strlen($text)>15) {
            $text = substr($text, 0, 15).'...';
        }
        return 'Project: '.$this->verbalizeProjectId($groupId, $withLink).', Artifact: '.$this->verbalizeArtifactId($artifactId, $changesetId, $withLink).', Text: '.$text;
    }

    /**
     * Verbalize artifact & changeset
     *
     * @param Integer $artifactId  Id of the artifact
     * @param Integer $changesetId Id of th changeset
     * @param Boolean $withLink    Create link for the params
     *
     * @return String
     */
    private function verbalizeArtifactId($artifactId, $changesetId, $withLink) {
        $txt = '#'.$artifactId;
        if ($withLink) {
            $txt = '<a href="/plugins/tracker/?aid='. $artifactId .'#followup_'.$changesetId.'">'. $txt .'</a>';
        }
        return $txt;
    }

}

?>