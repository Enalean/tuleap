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

require_once dirname(__FILE__).'/../../include/Planning/PlanningController.class.php';

Mock::generate('Tracker_ArtifactFactory');
Mock::generate('PlanningFactory');

class PlanningController_TestBuilder {
    
    public function __construct() {
        $this->request          = new Codendi_Request(array());
        $this->planning_factory = new MockPlanningFactory();
    }
    
    public function withRequest(Codendi_Request_TestBuilder $request) {
        $this->request = $request->build();
        return $this;
    }

    public function withPlanningFactory($planning_factory) {
        $this->planning_factory = $planning_factory;
        return $this;
    }
    
    public function build() {
        return new Planning_Controller($this->request,
                                       $this->planning_factory);
    }
}

function aPlanningController() {
    return new PlanningController_TestBuilder();
}
?>
