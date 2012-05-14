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

require_once(dirname(__FILE__).'/../../include/Planning/Milestone.class.php');

function aMockMilestone() {
    return new Test_Planning_MockMilestoneBuilder();
}

class Test_Planning_MockMilestoneBuilder {
    public function __construct() {
        $this->milestone = mock('Planning_Milestone');
    }
    
    public function withArtifact(Tracker_Artifact $artifact) {
        $root_node = new TreeNode(array('id'    => $artifact->getId(),
                                        'title' => $artifact->getTitle()));
        $root_node->setId($artifact->getId());
        
        stub($this->milestone)->getArtifact()->returns($artifact);
        stub($this->milestone)->getPlannedArtifacts()->returns($root_node);
        
        return $this;
    }
    
    public function withReadPermission() {
        stub($this->milestone)->userCanView()->returns(true);
        return $this;
    }
    
    public function build() {
        return $this->milestone;
    }
}
?>
