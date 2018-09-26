<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\User;

use PFUser;
use Project;
use TuleapTestCase;
use UserHelper;
use UserImport;
use UserManager;

class UserImportTest extends TuleapTestCase
{
    private $user_email_filename;
    private $user_filename;
    /**
     * @var UserImport
     */
    private $user_import;

    /**
     * @var UserHelper
     */
    private $user_helper;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var Project
     */
    private $project;

    public function setUp()
    {
        parent::setUp();

        $this->user_helper         = mock('UserHelper');
        $this->project             = aMockProject()->withId(110)->build();
        $this->user_manager        = mock('UserManager');
        $this->user_filename       = __DIR__.'/_fixtures/user_import.txt';
        $this->user_email_filename = __DIR__.'/_fixtures/user_email_import.txt';
        $this->user_import         = new UserImport($this->project->getID(), $this->user_manager, $this->user_helper);
    }

    public function tearDown()
    {
        parent::tearDown();

        UserManager::clearInstance();
    }

    public function itImportsUserByUserName()
    {
        $user = $this->getUser(102);

        stub($this->user_manager)->findUser('zurg')->returns($user);

        $user_collection = $this->user_import->parse($this->user_filename);

        $expected_user = array(
            'has_avatar'       => 'false',
            'user_name'        => 'zurg',
            'email'            => 'zurg@example.com',
            'profile_page_url' => '/users/zurg/',
            'username_display' => 'getDisplayName'
        );

        $this->assertEqual($user_collection->getFormattedUsers(), array($expected_user));
        $this->assertEqual($user_collection->getWarningsMultipleUsers(), null);
        $this->assertEqual($user_collection->getWarningsInvalidUsers(), null);
    }

    public function itImportsUserByEmail()
    {
        $user = $this->getUser(102);

        stub($this->user_manager)->getAllUsersByEmail('zurg@example.com')->returns(array($user));

        $user_collection = $this->user_import->parse($this->user_email_filename);

        $expected_user = array(
            'has_avatar'       => 'false',
            'user_name'        => 'zurg',
            'email'            => 'zurg@example.com',
            'profile_page_url' => '/users/zurg/',
            'username_display' => 'getDisplayName'
        );

        $this->assertEqual($user_collection->getFormattedUsers(), array($expected_user));
        $this->assertEqual($user_collection->getWarningsMultipleUsers(), null);
        $this->assertEqual($user_collection->getWarningsInvalidUsers(), null);
    }

    public function itDoesNotImportUserByEmailIfEmailLinkedToMultipleUsers()
    {
        $user  = $this->getUser(102);
        $user2 = $this->getUser(103);

        stub($this->user_manager)->getAllUsersByEmail('zurg@example.com')->returns(array($user, $user2));

        $user_collection = $this->user_import->parse($this->user_email_filename);

        $this->assertEqual($user_collection->getFormattedUsers(), null);
        $this->assertEqual(
            $user_collection->getWarningsMultipleUsers(),
            array(
                array('warning' => 'zurg@example.com has multiple corresponding users.')
            )
        );
        $this->assertEqual($user_collection->getWarningsInvalidUsers(), null);
    }

    public function itDoesNotImportUserIfUserNameDoesNotExist()
    {
        stub($this->user_manager)->findUser('zurg')->returns(null);
        stub($this->user_manager)->getAllUsersByEmail()->returns([]);

        $user_collection = $this->user_import->parse($this->user_filename);

        $this->assertEqual($user_collection->getFormattedUsers(), null);
        $this->assertEqual($user_collection->getWarningsMultipleUsers(), null);
        $this->assertEqual(
            $user_collection->getWarningsInvalidUsers(),
            array(
                array('warning' => "User 'zurg' does not exist")
            )
        );
    }

    /**
     * @return PFUser
     */
    private function getUser($id)
    {
        $user = mock('PFUser');
        stub($user)->isActive()->returns(true);
        stub($user)->isMember($this->project->getID())->returns(false);
        stub($user)->getEmail()->returns('zurg@example.com');
        stub($user)->getUserName()->returns('zurg');
        stub($user)->hasAvatar()->returns('false');
        stub($user)->getId()->returns($id);
        stub($this->user_helper)->getDisplayName()->returns('getDisplayName');

        return $user;
    }
}
