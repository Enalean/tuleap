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

require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Artifact/Tracker_Artifact.class.php';

class Planning_Item {
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    public function __construct(Tracker_Artifact $artifact) {
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
}

class Planning_PlannifiableItem extends Planning_Item {
    
    public function isPlannifiable() {
        return true;
    }
}

class Planning_BacklogItem extends Planning_Item {
    
    public function isPlannifiable() {
        return false;
    }
}

?>
