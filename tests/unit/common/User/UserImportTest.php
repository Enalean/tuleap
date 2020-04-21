<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User;

use PFUser;
use Project;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use UserHelper;
use UserImport;
use UserManager;

final class UserImportTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_helper         = \Mockery::spy(\UserHelper::class);
        $this->project             = \Mockery::spy(\Project::class, ['getID' => 110, 'getUnixName' => false, 'isPublic' => false]);
        $this->user_manager        = \Mockery::spy(\UserManager::class);
        $this->user_filename       = __DIR__ . '/_fixtures/user_import.txt';
        $this->user_email_filename = __DIR__ . '/_fixtures/user_email_import.txt';
        $this->user_import         = new UserImport($this->user_manager, $this->user_helper, \Mockery::mock(ProjectMemberAdder::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        UserManager::clearInstance();
    }

    public function testItImportsUserByUserName(): void
    {
        $user = $this->getUser(102);

        $this->user_manager->shouldReceive('findUser')->with('zurg')->andReturns($user);

        $user_collection = $this->user_import->parse($this->project->getID(), $this->user_filename);

        $expected_user = array(
            'has_avatar'       => 'false',
            'user_name'        => 'zurg',
            'email'            => 'zurg@example.com',
            'profile_page_url' => '/users/zurg/',
            'username_display' => 'getDisplayName',
            'avatar_url'       => ''
        );

        $this->assertEquals(array($expected_user), $user_collection->getFormattedUsers());
        $this->assertEmpty($user_collection->getWarningsMultipleUsers());
        $this->assertEmpty($user_collection->getWarningsInvalidUsers());
    }

    public function testItImportsUserByEmail(): void
    {
        $user = $this->getUser(102);

        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('zurg@example.com')->andReturns(array($user));

        $user_collection = $this->user_import->parse($this->project->getID(), $this->user_email_filename);

        $expected_user = array(
            'has_avatar'       => 'false',
            'user_name'        => 'zurg',
            'email'            => 'zurg@example.com',
            'profile_page_url' => '/users/zurg/',
            'username_display' => 'getDisplayName',
            'avatar_url'       => ''
        );

        $this->assertEquals(array($expected_user), $user_collection->getFormattedUsers());
        $this->assertEmpty($user_collection->getWarningsMultipleUsers());
        $this->assertEmpty($user_collection->getWarningsInvalidUsers());
    }

    public function testItDoesNotImportUserByEmailIfEmailLinkedToMultipleUsers(): void
    {
        $user  = $this->getUser(102);
        $user2 = $this->getUser(103);

        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('zurg@example.com')->andReturns(array($user, $user2));

        $user_collection = $this->user_import->parse($this->project->getID(), $this->user_email_filename);

        $this->assertEmpty($user_collection->getFormattedUsers());
        $this->assertEquals(
            array(
                array('warning' => 'zurg@example.com has multiple corresponding users.')
            ),
            $user_collection->getWarningsMultipleUsers(),
        );
        $this->assertEmpty($user_collection->getWarningsInvalidUsers());
    }

    public function testItDoesNotImportUserIfUserNameDoesNotExist(): void
    {
        $this->user_manager->shouldReceive('findUser')->with('zurg')->andReturns(null);
        $this->user_manager->shouldReceive('getAllUsersByEmail')->andReturns([]);

        $user_collection = $this->user_import->parse($this->project->getID(), $this->user_filename);

        $this->assertEmpty($user_collection->getFormattedUsers());
        $this->assertEmpty($user_collection->getWarningsMultipleUsers());
        $this->assertEquals(
            array(
                array('warning' => "User 'zurg' does not exist")
            ),
            $user_collection->getWarningsInvalidUsers()
        );
    }

    private function getUser(int $id): PFUser
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isActive')->andReturns(true);
        $user->shouldReceive('isMember')->with($this->project->getID())->andReturns(false);
        $user->shouldReceive('getEmail')->andReturns('zurg@example.com');
        $user->shouldReceive('getUserName')->andReturns('zurg');
        $user->shouldReceive('hasAvatar')->andReturns('false');
        $user->shouldReceive('getId')->andReturns($id);
        $this->user_helper->shouldReceive('getDisplayName')->andReturns('getDisplayName');

        return $user;
    }
}
