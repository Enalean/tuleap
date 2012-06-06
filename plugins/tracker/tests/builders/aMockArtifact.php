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

require_once dirname(__FILE__).'/../../include/Tracker/Tracker.class.php';
require_once dirname(__FILE__).'/../../include/Tracker/Artifact/Tracker_Artifact.class.php';

Mock::generate('Tracker');
Mock::generate('Tracker_Artifact');

class MockArtifactBuilder {
    public function __construct() {
        $this->id       = 123;
        $this->tracker  = new MockTracker();
        $this->title    = '';
        $this->artifact = new MockTracker_Artifact();
        $this->linkedArtifacts  = array();
        $this->uniqueLinkedArtifacts  = array();
        $this->uri      = '';
        $this->xref     = '';
    }

    /**
     * @return \MockArtifactBuilder 
     */
    public function withId($id) {
        $this->id = $id;
        return $this;
    }
    
    /**
     * @return \MockArtifactBuilder 
     */
    public function withTracker(Tracker $tracker) {
        $this->tracker = $tracker;
        return $this;
    }
    
    public function withTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * @return \MockArtifactBuilder 
     */
    public function withLinkedArtifacts($linkedArtifacts) {
        $this->linkedArtifacts = $linkedArtifacts;
        return $this;
    }
    
    /**
     * @return \MockArtifactBuilder 
     */
    public function withUniqueLinkedArtifacts($uniqueLinkedArtifacts) {
        $this->uniqueLinkedArtifacts = $uniqueLinkedArtifacts;
        return $this;
    }
    
    public function withUri($uri) {
        $this->uri = $uri;
        return $this;
    }
    
    public function withXRef($xref) {
        $this->xref = $xref;
        return $this;
    }
    
    public function build() {
        $this->artifact->setReturnValue('getId', $this->id);
        $this->artifact->setReturnValue('getTracker', $this->tracker);
        $this->artifact->setReturnValue('getTitle', $this->title);
        $this->artifact->setReturnValue('getUri', $this->uri);
        $this->artifact->setReturnValue('getXRef', $this->xref);
        $this->artifact->setReturnValue('getLinkedArtifacts', $this->linkedArtifacts);
        $this->artifact->setReturnValue('getUniqueLinkedArtifacts', $this->uniqueLinkedArtifacts);
        
        return $this->artifact;
    }
}

function aMockArtifact() { return new MockArtifactBuilder(); }
?>
