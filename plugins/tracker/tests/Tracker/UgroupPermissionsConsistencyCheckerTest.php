<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';

class Tracker_UgroupPermissionsConsistencyCheckerTest extends TuleapTestCase
{

    protected $template_tracker;
    protected $target_project;
    protected $permissions_retriever;
    protected $ugroup_manager;
    protected $template_ugroup_dev;
    protected $template_ugroup_support;
    protected $target_ugroup_dev;
    protected $target_ugroup_support;

    protected $tracker_id                 = 101;
    protected $template_ugroup_dev_id     = 123;
    protected $template_ugroup_support_id = 124;

    /**
     * @var Tracker_UgroupPermissionsConsistencyChecker
     */
    protected $checker;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->template_ugroup_dev     = mockery_stub(\ProjectUGroup::class)->getName()->returns('dev');
        $this->template_ugroup_support = mockery_stub(\ProjectUGroup::class)->getName()->returns('support');
        $this->target_ugroup_dev       = mockery_stub(\ProjectUGroup::class)->getName()->returns('dev');
        $this->target_ugroup_support   = mockery_stub(\ProjectUGroup::class)->getName()->returns('support');

        $template_project            = mockery_stub(\Project::class)->getID()->returns(103);
        $this->template_tracker      = aTracker()->withProject($template_project)->build();
        $this->target_project        = mockery_stub(\Project::class)->getID()->returns(104);
        $this->ugroup_manager        = \Mockery::spy(\UGroupManager::class);
        $this->messenger             = \Mockery::spy(\Tracker_UgroupPermissionsConsistencyMessenger::class);
        $this->permissions_retriever = \Mockery::spy(\Tracker_UgroupPermissionsGoldenRetriever::class);

        $this->checker = new Tracker_UgroupPermissionsConsistencyChecker(
            $this->permissions_retriever,
            $this->ugroup_manager,
            $this->messenger
        );
    }
}

class Tracker_UgroupPermissionsConsistencyChecker_SameProjectTest extends Tracker_UgroupPermissionsConsistencyCheckerTest
{

    public function itReturnsNoMessage()
    {
        expect($this->messenger)->allIsWell()->once();
        expect($this->messenger)->ugroupsMissing()->never();
        expect($this->messenger)->ugroupsAreTheSame()->never();

        $this->checker->checkConsistency($this->template_tracker, $this->template_tracker->getProject());
    }
}

class Tracker_UgroupPermissionsConsistencyChecker_NoPermOnStaticGroupsTest extends Tracker_UgroupPermissionsConsistencyCheckerTest
{

    public function itReturnsNoMessage()
    {
        stub($this->permissions_retriever)->getListOfInvolvedStaticUgroups($this->template_tracker)->returns([]);

        expect($this->messenger)->allIsWell()->once();
        expect($this->messenger)->ugroupsMissing()->never();
        expect($this->messenger)->ugroupsAreTheSame()->never();

        $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }
}

class Tracker_UgroupPermissionsConsistencyChecker_PermOnOneStaticGroupTest extends Tracker_UgroupPermissionsConsistencyCheckerTest
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        stub($this->permissions_retriever)->getListOfInvolvedStaticUgroups($this->template_tracker)->returns(array($this->template_ugroup_dev));
    }

    public function itReturnsAWarningWhenTheTargetProjectDoesNotHaveTheStaticGroup()
    {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array($this->target_ugroup_support));

        expect($this->messenger)->allIsWell()->never();
        expect($this->messenger)->ugroupsMissing()->once();
        expect($this->messenger)->ugroupsAreTheSame()->never();

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }

    public function itReturnsAnInfoWhenTheTargetProjectHasTheStaticGroup()
    {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array($this->target_ugroup_dev));

        expect($this->messenger)->allIsWell()->never();
        expect($this->messenger)->ugroupsMissing()->never();
        expect($this->messenger)->ugroupsAreTheSame()->once();

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }
}

class Tracker_UgroupPermissionsConsistencyChecker_PermOnManyStaticGroupTest extends Tracker_UgroupPermissionsConsistencyCheckerTest
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        stub($this->permissions_retriever)->getListOfInvolvedStaticUgroups($this->template_tracker)->returns(array($this->template_ugroup_dev, $this->template_ugroup_support));
    }

    public function itReturnsAWarningWhenTheTargetProjectDoesNotHaveTheStaticGroups()
    {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array());

        expect($this->messenger)->allIsWell()->never();
        expect($this->messenger)->ugroupsMissing()->once();
        expect($this->messenger)->ugroupsAreTheSame()->never();

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }

    public function itReturnsAWarningWhenTheTargetProjectDoesNotHaveOneOfTheStaticGroups()
    {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array($this->target_ugroup_dev));

        expect($this->messenger)->allIsWell()->never();
        expect($this->messenger)->ugroupsMissing()->once();
        expect($this->messenger)->ugroupsAreTheSame()->never();

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }

    public function itReturnsAnInfoWhenTheTargetProjectHasTheStaticGroups()
    {
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(array($this->target_ugroup_dev, $this->target_ugroup_support));

        expect($this->messenger)->allIsWell()->never();
        expect($this->messenger)->ugroupsMissing()->never();
        expect($this->messenger)->ugroupsAreTheSame()->once();

        $message = $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }
}
