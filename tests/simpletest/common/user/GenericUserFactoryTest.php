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

    public function setUp() {
        parent::setUp();
        $this->user_manager = mock('UserManager');
        $this->project_manager = mock('ProjectManager');
        $this->project = mock('Project');
        stub($this->project_manager)->getProject()->returns($this->project);
        $dao = mock('GenericUserDao');

        Config::store();

        Config::set(GenericUserFactory::CONFIG_KEY_SUFFIX, '');
        $this->factory = new GenericUserFactory($this->user_manager, $this->project_manager, $dao);
    }

    public function tearDown() {
        Config::restore();
        parent::tearDown();
    }

    public function testCreateReturnsGenericUserWithCorrectId() {

        $group_id = '120';
        $password = 'my_password';

        $generic_user = $this->factory->create($group_id, $password);
        $this->assertIsA($generic_user, 'GenericUser');

        $this->assertEqual($generic_user->getPassword(), 'my_password');
        $this->assertEqual($generic_user->getProject(), $this->project);
    }

    public function itCreatesUserWithNoSuffixByDefault() {
        $project_name = 'vla';
        stub($this->project)->getUnixName()->returns($project_name);

        $generic_user = $this->factory->create('120', 'my_password');
        $this->assertEqual(substr($generic_user->getUnixName(), -strlen($project_name)), $project_name);
    }

    public function itCreatesUserWithPrefixSetFromConfig() {
        $suffix = '-team';
        Config::set(GenericUserFactory::CONFIG_KEY_SUFFIX, $suffix);

        $generic_user = $this->factory->create('120', 'my_password');
        $this->assertEqual(substr($generic_user->getUnixName(), -strlen($suffix)), $suffix);
    }
}
?>
