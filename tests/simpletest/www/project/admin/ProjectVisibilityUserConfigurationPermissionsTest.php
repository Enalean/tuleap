<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\Project\Admin;

use ForgeAccess;
use ForgeConfig;
use TuleapTestCase;

class ProjectVisibilityUserConfigurationPermissionsTest extends TuleapTestCase
{
    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var ProjectVisibilityUserConfigurationPermissions
     */
    private $project_visibility_configuration_permission;

    /**
     * @var \Project
     */
    private $project;

    public function setUp()
    {
        $this->user                                        = mock('PFUser');
        $this->project                                     = mock('Project');
        $this->project_visibility_configuration_permission = new ProjectVisibilityUserConfigurationPermissions();
        ForgeConfig::store();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
    }

    public function itAllowsSiteAdministratorToConfigureProjectVisibility()
    {
        stub($this->user)->isSuperUser()->returns(true);
        ForgeConfig::set(ForgeAccess::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY, true);
        $this->assertTrue(
            $this->project_visibility_configuration_permission->canUserConfigureProjectVisibility(
                $this->user,
                $this->project
            )
        );
    }

    public function itAllowsSiteAdministratorToConfigureProjectVisibilityEvenWhenProjectAdminCanNotChooseVisibility()
    {
        stub($this->user)->isSuperUser()->returns(true);
        ForgeConfig::set(ForgeAccess::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY, true);
        $this->assertTrue(
            $this->project_visibility_configuration_permission->canUserConfigureProjectVisibility(
                $this->user,
                $this->project
            )
        );
    }

    public function itAllowsProjectAdministratorToConfigureProjectVisibility()
    {
        stub($this->user)->isSuperUser()->returns(false);
        stub($this->user)->isAdmin()->returns(true);
        ForgeConfig::set(ForgeAccess::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY, true);
        $this->assertTrue(
            $this->project_visibility_configuration_permission->canUserConfigureProjectVisibility(
                $this->user,
                $this->project
            )
        );
    }

    public function itDoesNotAllowProjectAdministratorToConfigureProjectVisibilityWhenUserIsNotProjectAdmin()
    {
        stub($this->user)->isSuperUser()->returns(false);
        stub($this->user)->isAdmin()->returns(false);
        ForgeConfig::set(ForgeAccess::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY, true);
        $this->assertFalse(
            $this->project_visibility_configuration_permission->canUserConfigureProjectVisibility(
                $this->user,
                $this->project
            )
        );
    }

    public function itDoesNotAllowProjectAdministratorToConfigureProjectVisibilityWhenUserIsProjectAdmin()
    {
        stub($this->user)->isSuperUser()->returns(false);
        stub($this->user)->isAdmin()->returns(true);
        ForgeConfig::set(ForgeAccess::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY, false);
        $this->assertFalse(
            $this->project_visibility_configuration_permission->canUserConfigureProjectVisibility(
                $this->user,
                $this->project
            )
        );
    }

    public function itAllowsSiteAdministratorToConfigureTruncatedEmails()
    {
        stub($this->user)->isSuperUser()->returns(true);
        $this->assertTrue(
            $this->project_visibility_configuration_permission->canUserConfigureTruncatedMail(
                $this->user,
                $this->project
            )
        );
    }

    public function itARefuseMereMortalsToConfigureTruncatedEmails()
    {
        stub($this->user)->isSuperUser()->returns(false);
        $this->assertFalse(
            $this->project_visibility_configuration_permission->canUserConfigureTruncatedMail(
                $this->user,
                $this->project
            )
        );
    }
}
