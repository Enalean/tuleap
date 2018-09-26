<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

Mock::generate('PlanningDao');
Mock::generate('TrackerFactory');
Mock::generate('PFUser');

function aPlanningFactory() {
    return new TestPlanningFactoryBuilder();
}

class TestPlanningFactoryBuilder {
    
    public $dao;
    public $tracker_factory;
    public $form_element_factory;
    public $planning_permissions_manager;
    
    public function __construct() {
        $this->dao                          = new MockPlanningDao();
        $this->tracker_factory              = new MockTrackerFactory();
        $this->form_element_factory         = mock('Tracker_FormElementFactory');
        $this->planning_permissions_manager = mock('PlanningPermissionsManager');
    }
    
    public function withDao(DataAccessObject $dao) {
        $this->dao = $dao;
        return $this;
    }
    
    public function withTrackerFactory(TrackerFactory $tracker_factory) {
        $this->tracker_factory = $tracker_factory;
        return $this;
    }

    public function withFormElementFactory(Tracker_FormElementFactory $factory) {
        $this->form_element_factory = $factory;
        return $this;
    }

    public function withPlanningPermissionsManager(PlanningPermissionsManager $planning_permissions_manager) {
        $this->planning_permissions_manager = $planning_permissions_manager;
        return $this;
    }

    public function build() {
        return new PlanningFactory($this->dao, $this->tracker_factory, $this->form_element_factory, $this->planning_permissions_manager);
    }
}

?>
