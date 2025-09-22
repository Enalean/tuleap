<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Timetracking\Widget\People;

use ProjectUGroup;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use Tuleap\Tracker\Tracker;

abstract class ManagerPermissionsTestBase extends TestIntegrationTestCase
{
    protected \PFUser $alice;
    protected \PFUser $bob;
    protected \PFUser $charlie;
    protected \PFUser $dylan;
    protected \PFUser $eleonor;
    protected \PFUser $frank;
    protected \PFUser $gaston;
    protected \PFUser $hector;
    protected \PFUser $igor;
    protected \PFUser $june;
    protected \PFUser $kevin;

    /**
     * @var Tracker[]
     */
    private array $trackers;

    /**
     * @var \PFUser[]
     */
    protected array $managers;

    /**
     * Scenario
     *
     * # In charmander project
     * Alice is Developer and is reporting her timesheets on a user story artifact
     * TIMETRACKING WRITE = Developer
     * TIMETRACKING READ = Managers
     *
     * # In squirtle project
     * Alice is member of project and is reporting her timesheets on an activity artifact
     * TIMETRACKING WRITE = Project members
     * TIMETRACKING READ = HR
     *
     * # In kakuna project
     * Alice is Accountant and is reporting her timesheets on an invoice artifact
     * TIMETRACKING WRITE = Accountant
     * TIMETRACKING READ = Project members
     *
     * # In rattata project
     * Alice is Analyst and is reporting her timesheets on a campaign artifact
     * TIMETRACKING WRITE = Analyst
     * TIMETRACKING READ = Analyst
     *
     * # In caterpie project
     * Alice is project member and is reporting her timesheets on a faq artifact
     * TIMETRACKING WRITE = Project members
     * TIMETRACKING READ = Nobody
     *
     *
     * Who can see Alice's times?
     *
     * - Bob because he has TIMETRACKING READ permission on the story tracker (member of Managers ugroup)
     * - Charlie because she is tracker admin (member of Integrators ugroup)
     * - Dylan because he is project admin
     * - Hector because he has TIMETRACKING READ permission on the activity tracker (member of HR ugroup)
     * - Igor because he has TIMETRACKING READ permission on the invoice tracker (project member)
     * - June because she is project member and project members are administrators of campaign tracker
     * - Kevin because he is project admin of caterpie project
     * - (siteadmin is not covered by this object)
     *
     * Who cannot see Alice's times?
     *
     * - Eleonor because she is only a project member
     * - Frank because he is only a registerd member
     * - Gaston because he has TIMETRACKING READ permission but on another tracker (member of External ugroup)
     */
    #[\Override]
    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection();

        $tracker_builder = new TrackerDatabaseBuilder($db->getDB());
        $core_builder    = new CoreDatabaseBuilder($db->getDB());

        $charmander_project    = $core_builder->buildProject('charmander-project');
        $charmander_project_id = (int) $charmander_project->getID();

        $squirtle_project    = $core_builder->buildProject('squirtle-project');
        $squirtle_project_id = (int) $squirtle_project->getID();

        $kakuna_project    = $core_builder->buildProject('kakuna-project');
        $kakuna_project_id = (int) $kakuna_project->getID();

        $rattata_project    = $core_builder->buildProject('rattata-project');
        $rattata_project_id = (int) $rattata_project->getID();

        $caterpie_project    = $core_builder->buildProject('caterpie-project');
        $caterpie_project_id = (int) $caterpie_project->getID();

        $dev_ugroup_id        = $core_builder->buildStaticUserGroup($charmander_project_id, 'Developers');
        $external_ugroup_id   = $core_builder->buildStaticUserGroup($charmander_project_id, 'External');
        $integrator_ugroup_id = $core_builder->buildStaticUserGroup($charmander_project_id, 'Integrators');
        $manager_ugroup_id    = $core_builder->buildStaticUserGroup($charmander_project_id, 'Managers');
        $hr_ugroup_id         = $core_builder->buildStaticUserGroup($squirtle_project_id, 'HR');
        $accountant_ugroup_id = $core_builder->buildStaticUserGroup($kakuna_project_id, 'accountant');
        $analyst_ugroup_id    = $core_builder->buildStaticUserGroup($rattata_project_id, 'analyst');

        $stories_tracker    = $tracker_builder->buildTracker($charmander_project_id, 'Stories');
        $bug_tracker        = $tracker_builder->buildTracker($charmander_project_id, 'Bugs');
        $activities_tracker = $tracker_builder->buildTracker($squirtle_project_id, 'Activities');
        $invoice_tracker    = $tracker_builder->buildTracker($kakuna_project_id, 'Invoices');
        $campaign_tracker   = $tracker_builder->buildTracker($rattata_project_id, 'Campaigns');
        $faq_tracker        = $tracker_builder->buildTracker($caterpie_project_id, 'FAQ');

        $this->trackers = [
            $stories_tracker,
            $bug_tracker,
            $activities_tracker,
            $invoice_tracker,
            $campaign_tracker,
            $faq_tracker,
        ];

        $tracker_builder->setViewPermissionOnTracker(
            $stories_tracker->getId(),
            Tracker::PERMISSION_ADMIN,
            $integrator_ugroup_id,
        );
        $tracker_builder->setViewPermissionOnTracker(
            $campaign_tracker->getId(),
            Tracker::PERMISSION_ADMIN,
            ProjectUGroup::PROJECT_MEMBERS,
        );

        $timetracking_ugroup_dao = new TimetrackingUgroupDao();
        $timetracking_ugroup_dao->saveWriters($stories_tracker->getId(), [$dev_ugroup_id]);
        $timetracking_ugroup_dao->saveReaders($stories_tracker->getId(), [$manager_ugroup_id]);
        $timetracking_ugroup_dao->saveReaders($bug_tracker->getId(), [$external_ugroup_id]);
        $timetracking_ugroup_dao->saveWriters($activities_tracker->getId(), [ProjectUGroup::PROJECT_MEMBERS]);
        $timetracking_ugroup_dao->saveReaders($activities_tracker->getId(), [$hr_ugroup_id]);
        $timetracking_ugroup_dao->saveWriters($invoice_tracker->getId(), [$accountant_ugroup_id]);
        $timetracking_ugroup_dao->saveReaders($invoice_tracker->getId(), [ProjectUGroup::PROJECT_MEMBERS]);
        $timetracking_ugroup_dao->saveWriters($campaign_tracker->getId(), [$analyst_ugroup_id]);
        $timetracking_ugroup_dao->saveReaders($campaign_tracker->getId(), [$analyst_ugroup_id]);
        $timetracking_ugroup_dao->saveWriters($faq_tracker->getId(), [ProjectUGroup::PROJECT_MEMBERS]);
        $timetracking_ugroup_dao->saveReaders($faq_tracker->getId(), [ProjectUGroup::NONE]);

        $this->alice   = $core_builder->buildUser('alice', 'Alice', 'alice@example.com');
        $this->bob     = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $this->charlie = $core_builder->buildUser('charlie', 'Charlie', 'charlie@example.com');
        $this->dylan   = $core_builder->buildUser('dylan', 'Dylan', 'dylan@example.com');
        $this->eleonor = $core_builder->buildUser('eleonor', 'Eleonor', 'eleonor@example.com');
        $this->frank   = $core_builder->buildUser('frank', 'Frank', 'frank@example.com');
        $this->gaston  = $core_builder->buildUser('gaston', 'Gaston', 'gaston@example.com');
        $this->hector  = $core_builder->buildUser('hector', 'Hector', 'hector@example.com');
        $this->igor    = $core_builder->buildUser('igor', 'Igor', 'igor@example.com');
        $this->june    = $core_builder->buildUser('june', 'June', 'june@example.com');
        $this->kevin   = $core_builder->buildUser('kevin', 'Kevin', 'kevin@example.com');

        $this->managers = [
            $this->bob,
            $this->charlie,
            $this->dylan,
            $this->eleonor,
            $this->frank,
            $this->gaston,
            $this->hector,
            $this->igor,
            $this->june,
            $this->kevin,
        ];

        $core_builder->addUserToStaticUGroup((int) $this->alice->getId(), $dev_ugroup_id);
        $core_builder->addUserToStaticUGroup((int) $this->bob->getId(), $manager_ugroup_id);
        $core_builder->addUserToStaticUGroup((int) $this->charlie->getId(), $integrator_ugroup_id);
        $core_builder->addUserToProjectMembers((int) $this->dylan->getId(), $charmander_project_id);
        $core_builder->addUserToProjectAdmins((int) $this->dylan->getId(), $charmander_project_id);
        $core_builder->addUserToProjectMembers((int) $this->eleonor->getId(), $charmander_project_id);
        $core_builder->addUserToStaticUGroup((int) $this->gaston->getId(), $external_ugroup_id);

        $core_builder->addUserToProjectMembers((int) $this->alice->getId(), $squirtle_project_id);
        $core_builder->addUserToStaticUGroup((int) $this->hector->getId(), $hr_ugroup_id);

        $core_builder->addUserToStaticUGroup((int) $this->alice->getId(), $accountant_ugroup_id);
        $core_builder->addUserToProjectMembers((int) $this->igor->getId(), $kakuna_project_id);

        $core_builder->addUserToStaticUGroup((int) $this->alice->getId(), $analyst_ugroup_id);
        $core_builder->addUserToProjectMembers((int) $this->june->getId(), $rattata_project_id);

        $core_builder->addUserToProjectMembers((int) $this->alice->getId(), $caterpie_project_id);
        $core_builder->addUserToProjectMembers((int) $this->kevin->getId(), $caterpie_project_id);
        $core_builder->addUserToProjectAdmins((int) $this->kevin->getId(), $caterpie_project_id);
    }

    protected function disableTimetrackingForTrackers(): void
    {
        $admin_dao = new AdminDao();
        foreach ($this->trackers as $tracker) {
            $admin_dao->disableTimetrackingForTracker($tracker->getId());
        }
    }

    protected function enableTimetrackingForTrackers(): void
    {
        $admin_dao = new AdminDao();
        foreach ($this->trackers as $tracker) {
            $admin_dao->enableTimetrackingForTracker($tracker->getId());
        }
    }
}
