<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once __DIR__.'/../../../../bootstrap.php';

class PermissionsTest extends TuleapTestCase {

    private $permission_manager;
    private $condition;
    private $user;
    private $transition;

    public function setUp() {
        parent::setUp();

        $this->user       = stub('PFUser')->getId()->returns(101);
        $this->project_id = 202;

        $this->permission_manager = stub('PermissionsManager')->getAuthorizedUgroups(
            303,
            Workflow_Transition_Condition_Permissions::PERMISSION_TRANSITION
        )->returns(array(404));

        PermissionsManager::setInstance($this->permission_manager);

        $this->transition = stub('Transition')->getId()->returns(303);
        $this->condition  = new Workflow_Transition_Condition_Permissions($this->transition);
    }

    public function tearDown() {
        PermissionsManager::clearInstance();

        parent::tearDown();
    }

    public function itReturnsTrueIfUserCanSeeTransition() {
        stub($this->user)->isMemberOfUGroup()->returns(true);

        $this->assertTrue($this->condition->isUserAllowedToSeeTransition($this->user, $this->project_id));
    }

    public function itReturnsFalseIfUserCannotSeeTransition() {
        stub($this->user)->isMemberOfUGroup()->returns(false);

        $this->assertFalse($this->condition->isUserAllowedToSeeTransition($this->user, $this->project_id));
    }
}