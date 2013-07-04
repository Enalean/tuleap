<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/user/UserManager.class.php';
require_once 'common/user/GenericUser.class.php';
require_once 'common/user/GenericUserFactory.class.php';
require_once 'common/dao/GenericUserDao.class.php';

class GenericUserFactoryTest extends TuleapTestCase {

    public function testCreateReturnsGenericUserWithCorrectId() {
        $user_manager = mock('UserManager');

        $project = mock('Project');
        $project_manager = stub('ProjectManager')->getProject()->returns($project);

        $dao = mock('GenericUserDao');

        $factory = new GenericUserFactory($user_manager, $project_manager, $dao);

        $group_id = '120';
        $password = 'my_password';

        $generic_user = $factory->create($group_id, $password);
        $this->assertIsA($generic_user, 'GenericUser');

        $this->assertEqual($generic_user->getPassword(), 'my_password');
        $this->assertEqual($generic_user->getProject(), $project);
    }
}
?>
