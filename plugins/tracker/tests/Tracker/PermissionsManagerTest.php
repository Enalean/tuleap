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

class Tracker_PermissionsManagerTest extends TuleapTestCase {

    protected $template_tracker;
    protected $target_project;
    protected $permissions_manager;
    protected $tracker_permissions_manager;
    protected $dynamic_project_member;
    protected $ugroup_dev;
    protected $ugroup_support;
    protected $tracker_id        = 101;
    protected $ugroup_dev_id     = 123;
    protected $ugroup_support_id = 124;

    public function setUp() {
        parent::setUp();

        $this->project = aMockProject()->withId(101)->build();
        $this->tracker = aTracker()->withId($this->tracker_id)->withProject($this->project)->build();
        $this->permissions_manager = mock('PermissionsManager');

        $this->ugroup_dev     = stub('UGroup')->getName()->returns('dev');
        $this->ugroup_support = stub('UGroup')->getName()->returns('support');

        $ugroup_manager = mock('UGroupManager');
        stub($ugroup_manager)->getUGroup($this->project, $this->ugroup_dev_id)->returns($this->ugroup_dev);
        stub($ugroup_manager)->getUGroup($this->project, $this->ugroup_support_id)->returns($this->ugroup_support);
        stub($ugroup_manager)->getUGroup($this->project, UGroup::PROJECT_MEMBERS)->returns(mock('UGroup'));

        $this->tracker_permissions_manager = new Tracker_PermissionsManager($this->permissions_manager, $ugroup_manager);
    }

    public function itReturnsTheListOfUGroupsThatHaveATrackerPermissionsOnTheGivenTracker() {
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_ACCESS_%')->returns(array($this->ugroup_dev_id));
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_ADMIN')->returns(array($this->ugroup_support_id));
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_FIELD_%')->returns(array());

        $ugroups = $this->tracker_permissions_manager->getListOfInvolvedStaticUgroups($this->tracker);

        $this->assertEqual($ugroups, array($this->ugroup_dev, $this->ugroup_support));
    }

    public function itReturnsTheListOfUGroupsThatHaveAFieldPermissionsOnTheGivenTracker() {
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_ACCESS_%')->returns(array($this->ugroup_dev_id));
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_ADMIN')->returns(array());
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_FIELD_%')->returns(array($this->ugroup_support_id));

        $ugroups = $this->tracker_permissions_manager->getListOfInvolvedStaticUgroups($this->tracker);

        $this->assertEqual($ugroups, array($this->ugroup_dev, $this->ugroup_support));
    }

    public function itDoesNotDuplicateGroups() {
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_ACCESS_%')->returns(array($this->ugroup_dev_id));
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_ADMIN')->returns(array($this->ugroup_support_id));
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_FIELD_%')->returns(array($this->ugroup_dev_id, $this->ugroup_support_id));

        $ugroups = $this->tracker_permissions_manager->getListOfInvolvedStaticUgroups($this->tracker);

        $this->assertEqual($ugroups, array($this->ugroup_dev, $this->ugroup_support));
    }

    public function itRemoveDynamicUGroups() {
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_ACCESS_%')->returns(array(UGroup::PROJECT_MEMBERS));
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_ADMIN')->returns(array());
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->tracker_id, 'PLUGIN_TRACKER_FIELD_%')->returns(array());

        $ugroups = $this->tracker_permissions_manager->getListOfInvolvedStaticUgroups($this->tracker);

        $this->assertEqual($ugroups, array());
    }
}
?>
