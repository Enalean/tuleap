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

require_once 'Item.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/Artifact/Tracker_Artifact.class.php';

/**
 * Allows using artifacts as planning items.
 */
class Planning_Artifact extends Planning_Item {
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    public function __construct(Tracker_Artifact $artifact, Planning $planning) {
        parent::__construct($planning);
        
        $this->artifact = $artifact;
    }

    public function getEditUri() {
        return $this->artifact->getUri();
    }

    public function getXRef() {
        return $this->artifact->getXRef();
    }
    
    public function getTitle() {
        return $this->artifact->getTitle();
    }

    public function getId() {
        return $this->artifact->getId();
    }
    
    public function getTracker() {
        return $this->artifact->getTracker();
    }
    
    public function isPlannifiable() {
        return ($this->getTracker()->getId() == $this->planning->getBacklogTrackerId());
    }
}
?>
