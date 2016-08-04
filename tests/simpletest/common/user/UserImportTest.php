<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once 'common/autoload.php';

class UserImportTest extends TuleapTestCase
{
    /**
     *
     * @var UserImport
     */
    private $user_import;

    public function setUp()
    {
        parent::setUp();

        $project_id                = 128;
        $this->user_import         = new UserImport($project_id);
        $this->user_manager        = mock('UserManager');
        $this->user_filename       = __DIR__.'/_fixtures/user_import.txt';
        $this->user_email_filename = __DIR__.'/_fixtures/user_email_import.txt';
        $this->user                = mock('PFUser');
        stub($this->user)->isActive()->returns(true);
        stub($this->user)->isMember($project_id)->returns(false);
        stub($this->user)->getId()->returns(102);
        UserManager::setInstance($this->user_manager);
    }

    public function tearDown()
    {
        parent::tearDown();

        UserManager::clearInstance();
    }

    public function itImportsUserByUserName()
    {
        $parsed_users = array();
        stub($this->user_manager)->findUser('zurg')->returns($this->user);

        $parsed = $this->user_import->parse($this->user_filename, $parsed_users);

        $this->assertTrue($parsed);
    }

    public function itImportsUserByEmail()
    {
        $parsed_users = array();
        stub($this->user_manager)->getAllUsersByEmail('zurg@example.com')->returns(array($this->user));

        $parsed = $this->user_import->parse($this->user_email_filename, $parsed_users);

        $this->assertTrue($parsed);
    }

    public function itDoesNotImportUserByEmailIfEmailLinkedToMultipleUsers()
    {
        $parsed_users = array();
        $user2        = mock('PFUser');
        stub($this->user_manager)->getAllUsersByEmail('zurg@example.com')->returns(array($this->user, $user2));

        $parsed = $this->user_import->parse($this->user_email_filename, $parsed_users);

        $this->assertFalse($parsed);
    }

    public function itDoesNotImportUserIfUserNameDoesNotExist()
    {
        $parsed_users = array();
        stub($this->user_manager)->findUser('zurg')->returns(null);

        $parsed = $this->user_import->parse($this->user_filename, $parsed_users);

        $this->assertFalse($parsed);
    }
}
