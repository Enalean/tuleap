<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_ArtifactLinkInfo {
    
    protected $artifact_id;
    protected $keyword;
    protected $group_id;
    protected $tracker_id;
    protected $last_changeset_id;
    
    /**
     * @param integer $artifact_id
     * @param string  $keyword
     * @param integer $group_id
     * @param integer $last_changeset_id
     */
    public function __construct($artifact_id, $keyword, $group_id, $tracker_id, $last_changeset_id) {
        $this->artifact_id       = $artifact_id; 
        $this->keyword           = $keyword;
        $this->group_id          = $group_id;
        $this->tracker_id        = $tracker_id;
        $this->last_changeset_id = $last_changeset_id;
    }

    /**
     * Instanciate a new object based on a artifact
     *
     * @param Tracker_Artifact $artifact
     *
     * @return Tracker_ArtifactLinkInfo
     */
    public static function buildFromArtifact(Tracker_Artifact $artifact) {
        $tracker = $artifact->getTracker();
        $klass   = __CLASS__;
        return new $klass(
            $artifact->getId(),
            $tracker->getItemName(),
            $tracker->getGroupId(),
            $tracker->getId(),
            $artifact->getLastChangeset()->getId()
        );
    }

    /**
     * @return int the id of the artifact link
     */
    public function getArtifactId() {
        return $this->artifact_id;
    }
    
    /**
     * @return string the keyword of the artifact link
     */
    public function getKeyword() {
        return $this->keyword;
    }
    
    /**
     * @return int the group_id of the artifact link
     */
    public function getGroupId() {
        return $this->group_id;
    }
    
    /**
     * @return int the tracker_id of the artifact link
     */
    public function getTrackerId() {
        return $this->tracker_id;
    }
    
    /**
     * Returns the tracker this artifact belongs to
     *
     * @return Tracker The tracker this artifact belongs to
     */
    public function getTracker() {
        return TrackerFactory::instance()->getTrackerByid($this->tracker_id);
    }
    
    /**
     * @return int the last changeset_id of the artifact link
     */
    public function getLastChangesetId() {
        return $this->last_changeset_id;
    }
    
    /**
     * Get the Url to the artifact link
     *
     * @return string the html code (a href) to this artifact link
     */
    public function getUrl() {
        $server_url = get_server_url();
        return '<a class="cross-reference" href="' . $server_url . '/goto?key=' . $this->getKeyword() . '&val=' . $this->getArtifactId() . '&group_id=' . $this->getGroupId() . '">' . $this->getLabel() . '</a>';
    }
    
    /**
     * Get the raw value of this artifact link (bug #1234, story #9876, etc.)
     *
     * @return string the raw value of this artifact link
     */
    public function getLabel() {
        return $this->getKeyword() . ' #' . $this->getArtifactId();
    }

    /**
     * Returns true is the current user can see the artifact
     *
     * @return boolean
     */
    public function userCanView() {
        $af = Tracker_ArtifactFactory::instance();
        $a  = $af->getArtifactById($this->artifact_id);
        return $a->userCanView();
    }
    
    public function __toString() {
        return $this->getLabel();
    }

}
?>
