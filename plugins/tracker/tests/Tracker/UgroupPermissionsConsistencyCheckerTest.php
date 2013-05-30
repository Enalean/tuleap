<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__) .'/../bootstrap.php';

class Tracker_UgroupPermissionsConsistencyCheckerTest extends TuleapTestCase {

    protected $template_tracker;
    protected $target_project;
    protected $permissions_manager;
    protected $ugroup_manager;
    protected $template_ugroup_dev;
    protected $template_ugroup_support;
    protected $target_ugroup_dev;
    protected $target_ugroup_support;

    public function setUp() {
        parent::setUp();
        $this->template_ugroup_dev     = stub('UGroup')->getName()->returns('dev');
        $this->template_ugroup_support = stub('UGroup')->getName()->returns('support');
        $this->target_ugroup_dev       = stub('UGroup')->getName()->returns('dev');
        $this->target_ugroup_support   = stub('UGroup')->getName()->returns('support');

        $this->template_tracker    = mock('Tracker');
        $this->target_project      = mock('Project');
        $this->permissions_manager = mock('Tracker_PermissionsManager');
        $this->ugroup_manager      = mock('UGroupManager');

        $this->checker = new Tracker_UgroupPermissionsConsistencyChecker($this->permissions_manager, $this->ugroup_manager);
    }
}

class Tracker_UgroupPermissionsConsistencyChecker_NoPermOnStaticGroupsTest extends Tracker_UgroupPermissionsConsistencyCheckerTest {

    public function itReturnsNoMessage() {
        stub($this->permissions_manager)->getListOfInvolvedStaticUgroups()->returns(array());

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);

        $this->assertIsA($message, 'Tracker_UgroupPermissionsConsistencyNoMessage');
    }

}

class Tracker_UgroupPermissionsConsistencyChecker_PermOnOneStaticGroupTest extends Tracker_UgroupPermissionsConsistencyCheckerTest {

    public function setUp() {
        parent::setUp();
        stub($this->permissions_manager)->getListOfInvolvedStaticUgroups()->returns(array($this->template_ugroup_dev));
    }

    public function itReturnsAWarningWhenTheTargetProjectDoesNotHaveTheStaticGroup() {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array($this->target_ugroup_support));

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);

        $this->assertIsA($message, 'Tracker_UgroupPermissionsConsistencyWarningMessage');
    }

    public function itReturnsAnInfoWhenTheTargetProjectHasTheStaticGroup() {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array($this->target_ugroup_dev));

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);

        $this->assertIsA($message, 'Tracker_UgroupPermissionsConsistencyInfoMessage');
    }
}

class Tracker_UgroupPermissionsConsistencyChecker_PermOnManyStaticGroupTest extends Tracker_UgroupPermissionsConsistencyCheckerTest {

    public function setUp() {
        parent::setUp();
        stub($this->permissions_manager)->getListOfInvolvedStaticUgroups()->returns(array($this->template_ugroup_dev, $this->template_ugroup_support));
    }

    public function itReturnsAWarningWhenTheTargetProjectDoesNotHaveTheStaticGroups() {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array());

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);

        $this->assertIsA($message, 'Tracker_UgroupPermissionsConsistencyWarningMessage');
    }

    public function itReturnsAWarningWhenTheTargetProjectDoesNotHaveOneOfTheStaticGroups() {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array($this->target_ugroup_dev));

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);

        $this->assertIsA($message, 'Tracker_UgroupPermissionsConsistencyWarningMessage');
    }

    public function itReturnsAnInfoWhenTheTargetProjectHasTheStaticGroups() {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array($this->target_ugroup_dev, $this->target_ugroup_support));

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);

        $this->assertIsA($message, 'Tracker_UgroupPermissionsConsistencyInfoMessage');
    }
}
?>
