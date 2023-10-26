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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use UserHelper;
use UserImport;
use UserManager;

final class UserImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private string $user_email_filename;
    private string $user_filename;
    private UserImport $user_import;

    /**
     * @var UserHelper&MockObject
     */
    private $user_helper;

    /**
     * @var UserManager&MockObject
     */
    private $user_manager;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_helper         = $this->createMock(\UserHelper::class);
        $this->project             = ProjectTestBuilder::aProject()->withId(110)->withAccessPrivate()->build();
        $this->user_manager        = $this->createMock(\UserManager::class);
        $this->user_filename       = __DIR__ . '/_fixtures/user_import.txt';
        $this->user_email_filename = __DIR__ . '/_fixtures/user_email_import.txt';
        $this->user_import         = new UserImport($this->user_manager, $this->user_helper, $this->createMock(ProjectMemberAdder::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        UserManager::clearInstance();
    }

    public function testItImportsUserByUserName(): void
    {
        $user = $this->getUser(102);

        $this->user_manager->method('findUser')->with('zurg')->willReturn($user);

        $user_collection = $this->user_import->parse((int) $this->project->getID(), $this->user_filename);

        $expected_user = [
            'has_avatar'       => 'false',
            'user_name'        => 'zurg',
            'email'            => 'zurg@example.com',
            'profile_page_url' => '/users/zurg/',
            'username_display' => 'getDisplayName',
            'avatar_url'       => '',
        ];

        self::assertEquals([$expected_user], $user_collection->getFormattedUsers());
        self::assertEmpty($user_collection->getWarningsMultipleUsers());
        self::assertEmpty($user_collection->getWarningsInvalidUsers());
    }

    public function testItImportsUserByEmail(): void
    {
        $user = $this->getUser(102);

        $this->user_manager->method('findUser')->willReturn(null);
        $this->user_manager->method('getAllUsersByEmail')->with('zurg@example.com')->willReturn([$user]);

        $user_collection = $this->user_import->parse((int) $this->project->getID(), $this->user_email_filename);

        $expected_user = [
            'has_avatar'       => 'false',
            'user_name'        => 'zurg',
            'email'            => 'zurg@example.com',
            'profile_page_url' => '/users/zurg/',
            'username_display' => 'getDisplayName',
            'avatar_url'       => '',
        ];

        self::assertEquals([$expected_user], $user_collection->getFormattedUsers());
        self::assertEmpty($user_collection->getWarningsMultipleUsers());
        self::assertEmpty($user_collection->getWarningsInvalidUsers());
    }

    public function testItDoesNotImportUserByEmailIfEmailLinkedToMultipleUsers(): void
    {
        $user  = $this->getUser(102);
        $user2 = $this->getUser(103);

        $this->user_manager->method('findUser')->willReturn(null);
        $this->user_manager->method('getAllUsersByEmail')->with('zurg@example.com')->willReturn([$user, $user2]);

        $user_collection = $this->user_import->parse((int) $this->project->getID(), $this->user_email_filename);

        self::assertEmpty($user_collection->getFormattedUsers());
        self::assertEquals(
            [
                ['warning' => 'zurg@example.com has multiple corresponding users.'],
            ],
            $user_collection->getWarningsMultipleUsers(),
        );
        self::assertEmpty($user_collection->getWarningsInvalidUsers());
    }

    public function testItDoesNotImportUserIfUserNameDoesNotExist(): void
    {
        $this->user_manager->method('findUser')->with('zurg')->willReturn(null);
        $this->user_manager->method('getAllUsersByEmail')->willReturn([]);

        $user_collection = $this->user_import->parse((int) $this->project->getID(), $this->user_filename);

        self::assertEmpty($user_collection->getFormattedUsers());
        self::assertEmpty($user_collection->getWarningsMultipleUsers());
        self::assertEquals(
            [
                ['warning' => "User 'zurg' does not exist"],
            ],
            $user_collection->getWarningsInvalidUsers()
        );
    }

    private function getUser(int $id): PFUser&MockObject
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isActive')->willReturn(true);
        $user->method('isMember')->with($this->project->getID())->willReturn(false);
        $user->method('getEmail')->willReturn('zurg@example.com');
        $user->method('getUserName')->willReturn('zurg');
        $user->method('getRealname')->willReturn('zorg');
        $user->method('hasAvatar')->willReturn('false');
        $user->method('getId')->willReturn($id);
        $user->method('getAvatarUrl')->willReturn('');
        $this->user_helper->method('getDisplayName')->willReturn('getDisplayName');

        return $user;
    }
}
