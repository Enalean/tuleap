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
        $this->artifact = new MockTracker_Artifact();
    }

    public function withId($id) {
        $this->id = $id;
        return $this;
    }
    
    public function withTitle($title) {
        $this->title = $title;
        return $this;
    }
    
    public function withLinkedArtifacts($linked_artifacts) {
        $this->linked_artifacts = $linked_artifacts;
        return $this;
    }
    
    public function withReadPermission() {
        stub($this->artifact)->userCanView()->returns(true);
        stub($this->tracker)->userCanView()->returns(true);
        return $this;
    }
    
    public function build() {
        stub($this->artifact)->getId()->returns($this->id);
        stub($this->artifact)->getTitle()->returns($this->title);
        stub($this->artifact)->fetchTitle()->returns("#$this->id $this->title");
        stub($this->artifact)->fetchDirectLinkToArtifact()->returns($this->id);
        stub($this->artifact)->getLinkedArtifacts()->returns($this->linked_artifacts);
        stub($this->artifact)->getTracker()->returns($this->tracker);
        
        return $this->artifact;
    }
}

function aMockArtifact() { return new MockArtifactBuilder(); }
?>
