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

// This is an on going work to help developers to build more expressive tests
// please add the functions/methods below when needed.
// For further information about the Test Data Builder pattern
// @see http://nat.truemesh.com/archives/000727.html
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

function anArtifact() {
    return new Test_Artifact_Builder();
}

class Test_Artifact_Builder {
    private $id;
    private $tracker;
    private $in_tracker_id;
    private $tracker_id;
    private $formElementFactory;
    private $changesets;
    private $hierarchy_factory;
    private $ancestors;

    public function withId($id) {
        $this->id = $id;
        return $this;
    }

    public function withTracker(Tracker $tracker) {
        $this->tracker    = $tracker;
        return $this->withTrackerId($tracker->getId());
    }
    
    public function withInTrackerId($in_tracker_id) {
        $this->in_tracker_id = $in_tracker_id;
        return $this;
    }

    public function withTrackerId($tracker_id) {
        $this->tracker_id = $tracker_id;
        return $this;
    }

    public function withFormElementFactory(Tracker_FormElementFactory $factory) {
        $this->formElementFactory = $factory;
        return $this;
    }

    public function withChangesets(array $changesets) {
        $this->changesets = $changesets;
        return $this;
    }

    public function withHierarchyFactory($hierarchy_factory) {
        $this->hierarchy_factory = $hierarchy_factory;
        return $this;
    }

    public function withoutParent() {
        $this->ancestors = array();
        return $this;
    }

    public function build() {
        $artifact = new Tracker_Artifact($this->id, $this->tracker_id, $this->in_tracker_id ,null, null, null);
        if ($this->tracker) {
            $artifact->setTracker($this->tracker);
        }
        if ($this->formElementFactory) {
            $artifact->setFormElementFactory($this->formElementFactory);
        }
        if ($this->changesets) {
            $artifact->setChangesets($this->changesets);
        }
        if ($this->hierarchy_factory) {
            $artifact->setHierarchyFactory($this->hierarchy_factory);
        }
        if (isset($this->ancestors)) {
            $artifact->setAllAncestors($this->ancestors);
        }
        return $artifact;
    }
}
?>