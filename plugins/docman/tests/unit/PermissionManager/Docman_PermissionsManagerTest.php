<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\PermissionManager;

use Docman_Item;
use Docman_PermissionsManager;
use Docman_PermissionsManagerDao;
use PermissionsManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Project_AccessPrivateException;
use Tuleap\Docman\Settings\ITellIfWritersAreAllowedToUpdatePropertiesOrDelete;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_PermissionsManagerTest extends TestCase
{
    use ForgeConfigSandbox;

    private PFUser $user;
    private Docman_PermissionsManager&MockObject $permissions_manager;
    private Project $project;
    private ProjectAccessChecker&MockObject $project_access_checker;
    private ITellIfWritersAreAllowedToUpdatePropertiesOrDelete&MockObject $forbid_writers_settings;

    public function setUp(): void
    {
        $this->user                = UserTestBuilder::anActiveUser()->withId(1234)->build();
        $this->project             = ProjectTestBuilder::aProject()->withId(102)->build();
        $this->permissions_manager = $this->createPartialMock(Docman_PermissionsManager::class, [
            'getProject',
            'getProjectAccessChecker',
            'getForbidWritersSettings',
            '_itemIsLockedForUser',
            '_isUserDocmanAdmin',
            '_getPermissionManagerInstance',
            'getDao',
        ]);
        $this->permissions_manager->method('getProject')->willReturn($this->project);
        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $this->permissions_manager->method('getProjectAccessChecker')->willReturn($this->project_access_checker);

        $this->forbid_writers_settings = $this->createMock(ITellIfWritersAreAllowedToUpdatePropertiesOrDelete::class);
        $this->permissions_manager->method('getForbidWritersSettings')->willReturn($this->forbid_writers_settings);

        \ForgeConfig::set(Docman_PermissionsManager::PLUGIN_OPTION_DELETE, false);
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     *           [false, true]
     *           [true, true]
     */
    public function testSuperUserHasAllAccess(bool $forbid_writers_to_update, bool $forbid_writers_to_delete): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        $this->permissions_manager->method('_getPermissionManagerInstance');
        $user = UserTestBuilder::anActiveUser()->withId(1234)->withSiteAdministrator()->build();
        $this->forbid_writers_settings->method('areWritersAllowedToUpdateProperties')->willReturn($forbid_writers_to_update);
        $this->forbid_writers_settings->method('areWritersAllowedToDelete')->willReturn($forbid_writers_to_delete);

        self::assertTrue($this->permissions_manager->userCanAdmin($user));
        self::assertTrue($this->permissions_manager->userCanRead($user, '2231'));
        self::assertTrue($this->permissions_manager->userCanWrite($user, '2112231'));
        self::assertTrue($this->permissions_manager->userCanManage($user, '2112231976'));
        self::assertTrue($this->permissions_manager->userCanDelete($user, new Docman_Item(['item_id' => 123])));
        self::assertTrue($this->permissions_manager->userCanUpdateItemProperties($user, new Docman_Item(['item_id' => 123])));
    }

    public function testAUserNotAbleToAccessTheProjectCanNotDoAnything(): void
    {
        $this->project_access_checker
            ->method('checkUserCanAccessProject')
            ->willThrowException(new Project_AccessPrivateException());

        self::assertFalse($this->permissions_manager->userCanAdmin($this->user));
        self::assertFalse($this->permissions_manager->userCanRead($this->user, '2231'));
        self::assertFalse($this->permissions_manager->userCanWrite($this->user, '2112231'));
        self::assertFalse($this->permissions_manager->userCanManage($this->user, '2112231976'));
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     *           [false, true]
     *           [true, true]
     */
    public function testDocmanAdminHasAllAccess(bool $forbid_writers_to_update, bool $forbid_writers_to_delete): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(true);
        $user = UserTestBuilder::anActiveUser()->withId(1324)->withoutSiteAdministrator()->build();
        $this->forbid_writers_settings->method('areWritersAllowedToUpdateProperties')->willReturn($forbid_writers_to_update);
        $this->forbid_writers_settings->method('areWritersAllowedToDelete')->willReturn($forbid_writers_to_delete);


        self::assertTrue($this->permissions_manager->userCanAdmin($user));

        $pm = $this->createMock(PermissionsManager::class);
        $pm->method('userHasPermission')->willReturn(false);
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        self::assertTrue($this->permissions_manager->userCanRead($user, '42231'));
        self::assertTrue($this->permissions_manager->userCanWrite($user, '52112231'));
        self::assertTrue($this->permissions_manager->userCanManage($user, '82112231976'));
        self::assertTrue($this->permissions_manager->userCanDelete($user, new Docman_Item(['item_id' => 123])));
        self::assertTrue($this->permissions_manager->userCanUpdateItemProperties($user, new Docman_Item(['item_id' => 123])));
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     *           [false, true]
     *           [true, true]
     */
    public function testManageRightGivesReadAndWriteRights(
        bool $forbid_writers_to_update,
        bool $forbid_writers_to_delete,
    ): void {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);
        $this->forbid_writers_settings->method('areWritersAllowedToUpdateProperties')->willReturn($forbid_writers_to_update);
        $this->forbid_writers_settings->method('areWritersAllowedToDelete')->willReturn($forbid_writers_to_delete);

        $parent_id    = 1500;
        $item_id      = 1515;
        $item         = new Docman_Item(['item_id' => $item_id, 'parent_id' => $parent_id]);
        $another_item = new Docman_Item(['item_id' => 123, 'parent_id' => $parent_id]);

        // Start Test
        $pm = $this->createMock(PermissionsManager::class);
        $pm->method('userHasPermission')->willReturnCallback(static fn(int $item_id, string $type) => match (true) {
            $item_id === $item->getId() && $type === 'PLUGIN_DOCMAN_MANAGE',
                $item_id === $item->getParentId() && $type === 'PLUGIN_DOCMAN_WRITE' => true,
            default                                                                  => false,
        });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);


        self::assertTrue($this->permissions_manager->userCanManage($user, $item->getId()));
        self::assertTrue($this->permissions_manager->userCanWrite($user, $item->getId()));
        self::assertTrue($this->permissions_manager->userCanRead($user, $item->getId()));
        self::assertTrue($this->permissions_manager->userCanDelete($user, $item));
        self::assertTrue($this->permissions_manager->userCanUpdateItemProperties($user, $item));

        self::assertFalse($this->permissions_manager->userCanManage($user, $another_item->getId()));
        self::assertFalse($this->permissions_manager->userCanWrite($user, $another_item->getId()));
        self::assertFalse($this->permissions_manager->userCanRead($user, $another_item->getId()));
        self::assertFalse($this->permissions_manager->userCanDelete($user, $another_item));
        self::assertFalse($this->permissions_manager->userCanUpdateItemProperties($user, $another_item));
    }

    // Functional test (should never change)
    public function testWriteRightGivesReadRight(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('isAdmin');
        $user->method('getId')->willReturn(1234);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);

        $itemId = 1515;

        // Start Test
        $pm = $this->createMock(PermissionsManager::class);
        $pm->method('userHasPermission')->willReturnCallback(static fn(int $item_id, string $type, array $ugroups) => match (true) {
            $item_id === $itemId && $type === 'PLUGIN_DOCMAN_WRITE' && $ugroups === ['test'] => true,
            default                                                                          => false,
        });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        self::assertFalse($this->permissions_manager->userCanManage($user, $itemId));
        self::assertTrue($this->permissions_manager->userCanWrite($user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($user, $itemId));

        // Test with another value for item_id
        self::assertFalse($this->permissions_manager->userCanManage($user, 123));
        self::assertFalse($this->permissions_manager->userCanWrite($user, 123));
        self::assertFalse($this->permissions_manager->userCanRead($user, 123));
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     *           [false, true]
     *           [true, true]
     */
    public function testReadRight(bool $forbid_writers_to_update, bool $forbid_writers_to_delete): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);
        $this->forbid_writers_settings->method('areWritersAllowedToUpdateProperties')->willReturn($forbid_writers_to_update);
        $this->forbid_writers_settings->method('areWritersAllowedToDelete')->willReturn($forbid_writers_to_delete);

        $itemId = 1515;

        // Start Test
        $pm = $this->createMock(PermissionsManager::class);
        $pm->method('userHasPermission')->willReturnCallback(static fn(?int $item_id, string $type, array $ugroups) => match (true) {
            $item_id === $itemId && $type === 'PLUGIN_DOCMAN_READ' && $ugroups === ['test'] => true,
            default                                                                         => false,
        });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        self::assertFalse($this->permissions_manager->userCanManage($user, $itemId));
        self::assertFalse($this->permissions_manager->userCanWrite($user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($user, $itemId));
        self::assertFalse($this->permissions_manager->userCanDelete($user, new Docman_Item(['item_id' => $itemId])));
        self::assertFalse($this->permissions_manager->userCanUpdateItemProperties($user, new Docman_Item(['item_id' => $itemId])));
    }

    // Functional test (should never change)
    public function testNoRight(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);

        $itemId = 1515;

        // Start Test
        $pm = $this->createMock(PermissionsManager::class);
        $pm->method('userHasPermission')->willReturn(false);
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        self::assertFalse($this->permissions_manager->userCanManage($user, $itemId));
        self::assertFalse($this->permissions_manager->userCanWrite($user, $itemId));
        self::assertFalse($this->permissions_manager->userCanRead($user, $itemId));
    }

    public function testUserCanWriteButItemIsLockedBySomeoneelse(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        // item is locked
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(true);

        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);

        $itemId = 1515;

        // User has write permission
        $pm = $this->createMock(PermissionsManager::class);
        $pm->method('userHasPermission')->willReturnCallback(static fn(?int $item_id, string $type, array $ugroups) => match (true) {
            $item_id === $itemId && $type === 'PLUGIN_DOCMAN_WRITE' && $ugroups === ['test'] => true,
            default                                                                          => false,
        });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        self::assertTrue($this->permissions_manager->userCanRead($user, $itemId));
        self::assertFalse($this->permissions_manager->userCanWrite($user, $itemId));
        self::assertFalse($this->permissions_manager->userCanManage($user, $itemId));
    }

    public function testExpectedQueriesOnRead(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);

        $itemId = 1515;

        // Start Test
        $pm      = $this->createMock(PermissionsManager::class);
        $matcher = self::exactly(3);
        $pm->expects($matcher)->method('userHasPermission')->willReturnCallback(function (...$parameters) use ($matcher, $itemId) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($itemId, $parameters[0]);
                self::assertSame('PLUGIN_DOCMAN_READ', $parameters[1]);
                self::assertSame(['test'], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($itemId, $parameters[0]);
                self::assertSame('PLUGIN_DOCMAN_WRITE', $parameters[1]);
                self::assertSame(['test'], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame($itemId, $parameters[0]);
                self::assertSame('PLUGIN_DOCMAN_MANAGE', $parameters[1]);
                self::assertSame(['test'], $parameters[2]);
            }
            return false;
        });

        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        self::assertFalse($this->permissions_manager->userCanRead($user, $itemId));
    }

    public function testExpectedQueriesOnWrite(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);

        $itemId = 1515;

        // Start Test
        $pm      = $this->createMock(PermissionsManager::class);
        $matcher = self::exactly(2);
        $pm->expects($matcher)->method('userHasPermission')->willReturnCallback(function (...$parameters) use ($matcher, $itemId) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($itemId, $parameters[0]);
                self::assertSame('PLUGIN_DOCMAN_WRITE', $parameters[1]);
                self::assertSame(['test'], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($itemId, $parameters[0]);
                self::assertSame('PLUGIN_DOCMAN_MANAGE', $parameters[1]);
                self::assertSame(['test'], $parameters[2]);
            }
            return false;
        });

        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        self::assertFalse($this->permissions_manager->userCanWrite($user, $itemId));
    }

    public function testExpectedQueriesOnManage(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);

        $itemId = 1515;

        // Start Test
        $pm = $this->createMock(PermissionsManager::class);
        $pm->expects(self::once())->method('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test'])->willReturn(false);

        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        self::assertFalse($this->permissions_manager->userCanManage($user, $itemId));
    }

    public function testCacheUserCanRead(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);

        $permission_manager = $this->createMock(PermissionsManager::class);
        $permission_manager->expects(self::exactly(4))->method('userHasPermission')
            ->willReturnCallback(static fn(string $item_id) => match ($item_id) {
                '1515' => false,
                '6667' => true,
            });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($permission_manager);

        // Start Test
        // Read one object
        self::assertFalse($this->permissions_manager->userCanRead($user, '1515'));

        // Test cache read og this object
        self::assertFalse($this->permissions_manager->userCanRead($user, '1515'));

        // Read perm for another object
        self::assertTrue($this->permissions_manager->userCanRead($user, '6667'));

        // Read 2nd time perm for second object
        self::assertTrue($this->permissions_manager->userCanRead($user, '6667'));

        // Read 3rd time first object perms
        self::assertFalse($this->permissions_manager->userCanRead($user, '1515'));

        // Read 3rd time second object perms
        self::assertTrue($this->permissions_manager->userCanRead($user, '6667'));
    }

    public function testCacheUserCanWrite(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);

        $permission_manager = $this->createMock(PermissionsManager::class);
        $permission_manager->expects(self::exactly(3))->method('userHasPermission')
            ->willReturnCallback(static fn(string $item_id) => match ($item_id) {
                '1515' => false,
                '6667' => true,
            });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($permission_manager);

        // Start Test
        // Read one object
        self::assertFalse($this->permissions_manager->userCanWrite($user, '1515'));

        // Test cache read og this object
        self::assertFalse($this->permissions_manager->userCanWrite($user, '1515'));

        // Read perm for another object
        self::assertTrue($this->permissions_manager->userCanWrite($user, '6667'));

        // Read 2nd time perm for second object
        self::assertTrue($this->permissions_manager->userCanWrite($user, '6667'));

        // Read 3rd time first object perms
        self::assertFalse($this->permissions_manager->userCanWrite($user, '1515'));

        // Read 3rd time second object perms
        self::assertTrue($this->permissions_manager->userCanWrite($user, '6667'));
    }

    public function testCacheUserCanManage(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);

        $permission_manager = $this->createMock(PermissionsManager::class);
        $permission_manager->expects(self::exactly(2))->method('userHasPermission')
            ->willReturnCallback(static fn(string $item_id) => match ($item_id) {
                '1515' => false,
                '6667' => true,
            });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($permission_manager);

        // Start Test
        // Read one object
        self::assertFalse($this->permissions_manager->userCanManage($user, '1515'));

        // Test cache read og this object
        self::assertFalse($this->permissions_manager->userCanManage($user, '1515'));

        // Read perm for another object
        self::assertTrue($this->permissions_manager->userCanManage($user, '6667'));

        // Read 2nd time perm for second object
        self::assertTrue($this->permissions_manager->userCanManage($user, '6667'));

        // Read 3rd time first object perms
        self::assertFalse($this->permissions_manager->userCanManage($user, '1515'));

        // Read 3rd time second object perms
        self::assertTrue($this->permissions_manager->userCanManage($user, '6667'));
    }

    public function testPermissionsBatchRetreivalForDocmanAdmin(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(true);
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);

        // No need to fetch perms when admin
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);
        $dao->expects(self::never())->method('retrievePermissionsForItems');

        $this->permissions_manager->method('getDao')->willReturn($dao);

        // Start Test
        $this->permissions_manager->retreiveReadPermissionsForItems([1515], $user);
        self::assertTrue($this->permissions_manager->userCanRead($user, '1515'));
    }

    public function testPermissionsBatchRetreivalForSuperUser(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(true);
        $user->method('getUgroups')->willReturn([]);

        // No need to fetch perms when admin
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);
        $dao->expects(self::never())->method('retrievePermissionsForItems');

        $this->permissions_manager->method('getDao')->willReturn($dao);

        // Start Test
        $this->permissions_manager->retreiveReadPermissionsForItems([1515], $user);
        self::assertTrue($this->permissions_manager->userCanRead($user, '1515'));
    }

    // {{{ Test all combination for batch permission settings (see retreiveReadPermissionsForItems)

    public function testSetUserCanManage(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        // Ensure everything comes from cache
        $this->permissions_manager->expects(self::never())->method('_isUserDocmanAdmin');
        $this->permissions_manager->expects(self::never())->method('_getPermissionManagerInstance');
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->expects(self::never())->method('isSuperUser');
        $user->method('getUgroups')->willReturn([]);

        $itemId = 1515;
        $this->permissions_manager->_setCanManage($user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanManage($user, $itemId));
        self::assertTrue($this->permissions_manager->userCanWrite($user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($user, $itemId));
    }

    public function testSetUserCanWrite(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        // Ensure everything comes from cache
        $this->permissions_manager->expects(self::never())->method('_isUserDocmanAdmin');
        $this->permissions_manager->expects(self::never())->method('_getPermissionManagerInstance');
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->expects(self::never())->method('isSuperUser');
        $user->method('getUgroups')->willReturn([]);

        $itemId = 1515;
        $this->permissions_manager->_setCanWrite($user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanWrite($user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($user, $itemId));
    }

    public function testSetUserCanRead(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        // Ensure everything comes from cache
        $this->permissions_manager->expects(self::never())->method('_isUserDocmanAdmin');
        $this->permissions_manager->expects(self::never())->method('_getPermissionManagerInstance');
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->expects(self::never())->method('isSuperUser');
        $user->method('getUgroups')->willReturn([]);

        $itemId = 1515;
        $this->permissions_manager->_setCanRead($user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanRead($user, $itemId));
    }

    // Read comes from cache but must look for write in DB
    public function testSetUserCanWriteAfterCanRead(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);

        $itemId = 1515;

        $pm = $this->createMock(PermissionsManager::class);
        $pm->method('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_WRITE', ['test'])->willReturn(true);
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        $this->permissions_manager->_setCanRead($user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanWrite($user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($user, $itemId));
    }

    // Read comes from cache but must look for manage in DB
    public function testSetUserCanManageAfterCanRead(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);

        $itemId = 1515;

        $pm = $this->createMock(PermissionsManager::class);
        $pm->method('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test'])->willReturn(true);
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        $this->permissions_manager->_setCanRead($user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanManage($user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($user, $itemId));
    }

    public function testSetUserCanReadWrite(): void
    {
        $itemId = 1515;
        $this->permissions_manager->_setCanRead($this->user->getId(), $itemId, true);
        $this->permissions_manager->_setCanWrite($this->user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanWrite($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanReadWriteManage(): void
    {
        $itemId = 1515;
        $this->permissions_manager->_setCanRead($this->user->getId(), $itemId, true);
        $this->permissions_manager->_setCanWrite($this->user->getId(), $itemId, true);
        $this->permissions_manager->_setCanManage($this->user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanManage($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanWrite($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanReadManage(): void
    {
        $itemId = 1515;
        $this->permissions_manager->_setCanRead($this->user->getId(), $itemId, true);
        $this->permissions_manager->_setCanManage($this->user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanManage($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanWrite($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanManageWrite(): void
    {
        $itemId = 1515;
        $this->permissions_manager->_setCanManage($this->user->getId(), $itemId, true);
        $this->permissions_manager->_setCanWrite($this->user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanManage($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanWrite($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanManageRead(): void
    {
        $itemId = 1515;
        $this->permissions_manager->_setCanManage($this->user->getId(), $itemId, true);
        $this->permissions_manager->_setCanRead($this->user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanManage($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanWrite($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanWriteRead(): void
    {
        $itemId = 1515;
        $this->permissions_manager->_setCanWrite($this->user->getId(), $itemId, true);
        $this->permissions_manager->_setCanRead($this->user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanWrite($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($this->user, $itemId));
    }

    // }}} Test all combination for batch permission settings (see retreiveReadPermissionsForItems)

    public function testSetUserCanManageButCannotRead(): void
    {
        $itemId = 1515;
        $this->permissions_manager->_setCanManage($this->user->getId(), $itemId, true);
        $this->permissions_manager->_setCanRead($this->user->getId(), $itemId, false);
        self::assertTrue($this->permissions_manager->userCanManage($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanWrite($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($this->user, $itemId));
    }

    public function testSetUserCannotReadButCanManage(): void
    {
        $itemId = 1515;
        $this->permissions_manager->_setCanRead($this->user->getId(), $itemId, false);
        $this->permissions_manager->_setCanManage($this->user->getId(), $itemId, true);
        self::assertTrue($this->permissions_manager->userCanManage($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanWrite($this->user, $itemId));
        self::assertTrue($this->permissions_manager->userCanRead($this->user, $itemId));
    }

    public function testGetDocmanManagerUsersError(): void
    {
        $pm  = $this->createMock(PermissionsManager::class);
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);

        $pm->expects(self::once())->method('getUgroupIdByObjectIdAndPermissionType')->willReturn(null);
        $dao->expects(self::never())->method('getUgroupMembers');
        $this->permissions_manager->expects(self::once())->method('getDao')->willReturn($dao);
        $this->permissions_manager->expects(self::once())->method('_getPermissionManagerInstance')->willReturn($pm);
        self::assertEquals([], $this->permissions_manager->getDocmanManagerUsers(1, $this->project));
    }

    public function testGetDocmanManagerUsersDynamicUgroup(): void
    {
        $dar = [['ugroup_id' => 101]];
        $pm  = $this->createMock(PermissionsManager::class);
        $this->permissions_manager->expects(self::once())->method('_getPermissionManagerInstance')->willReturn($pm);
        $dao     = $this->createMock(Docman_PermissionsManagerDao::class);
        $members = [
            [
                'email'       => 'john.doe@example.com',
                'language_id' => 'en_US',
            ],
            [
                'email'       => 'jane.doe@example.com',
                'language_id' => 'fr_FR',
            ],
        ];
        $dao->method('getUgroupMembers')->with(101)->willReturn($members);
        $this->permissions_manager->expects(self::once())->method('getDao')->willReturn($dao);

        $pm->expects(self::once())->method('getUgroupIdByObjectIdAndPermissionType')->willReturn($dar);
        $userArray = [
            'john.doe@example.com' => 'en_US',
            'jane.doe@example.com' => 'fr_FR',
        ];
        self::assertEquals($userArray, $this->permissions_manager->getDocmanManagerUsers(1, $this->project));
    }

    public function testGetDocmanManagerUsersEmptyDynamicUgroup(): void
    {
        $dar = [['ugroup_id' => 101]];
        $pm  = $this->createMock(PermissionsManager::class);
        $this->permissions_manager->expects(self::once())->method('_getPermissionManagerInstance')->willReturn($pm);
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);
        $dao->method('getUgroupMembers')->with(101)->willReturn([]);
        $dao->method('getDocmanAdminUgroups')->with($this->project)->willReturn([]);
        $this->permissions_manager->expects(self::once())->method('getDao')->willReturn($dao);

        $pm->expects(self::once())->method('getUgroupIdByObjectIdAndPermissionType')->willReturn($dar);
        self::assertEquals([], $this->permissions_manager->getDocmanManagerUsers(1, $this->project));
    }

    public function testGetDocmanManagerUsersStaticUgroup(): void
    {
        $dar = [['ugroup_id' => 100]];
        $pm  = $this->createMock(PermissionsManager::class);
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);
        $this->permissions_manager->method('getDao')->willReturn($dao);

        $pm->expects(self::once())->method('getUgroupIdByObjectIdAndPermissionType')->willReturn($dar);
        $dao->expects(self::never())->method('getUgroupMembers');
        self::assertEquals([], $this->permissions_manager->getDocmanManagerUsers(1, $this->project));
    }

    public function testGetDocmanAdminUsersError(): void
    {
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);
        $this->permissions_manager->method('getDao')->willReturn($dao);

        $dao->expects(self::once())->method('getDocmanAdminUgroups')->willReturn(null);
        $dao->expects(self::never())->method('getUgroupMembers');
        self::assertEquals([], $this->permissions_manager->getDocmanAdminUsers($this->project));
    }

    public function testGetDocmanAdminUsersDynamicUgroup(): void
    {
        $dar     = [['ugroup_id' => 101]];
        $dao     = $this->createMock(Docman_PermissionsManagerDao::class);
        $members = [
            [
                'email'       => 'john.doe@example.com',
                'language_id' => 'en_US',
            ],
            [
                'email'       => 'jane.doe@example.com',
                'language_id' => 'fr_FR',
            ],
        ];
        $dao->method('getUgroupMembers')->with(101)->willReturn($members);
        $this->permissions_manager->expects(self::once())->method('getDao')->willReturn($dao);

        $dao->expects(self::once())->method('getDocmanAdminUgroups')->willReturn($dar);
        $userArray = [
            'john.doe@example.com' => 'en_US',
            'jane.doe@example.com' => 'fr_FR',
        ];
        self::assertEquals($userArray, $this->permissions_manager->getDocmanAdminUsers($this->project));
    }

    public function testGetDocmanAdminUsersEmptyDynamicUgroup(): void
    {
        $dar = [['ugroup_id' => 101]];
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);
        $dao->method('getUgroupMembers')->with(101)->willReturn([]);
        $this->permissions_manager->expects(self::once())->method('getDao')->willReturn($dao);

        $dao->expects(self::once())->method('getDocmanAdminUgroups')->willReturn($dar);
        self::assertEquals([], $this->permissions_manager->getDocmanAdminUsers($this->project));
    }

    public function testGetDocmanAdminUsersStaticUgroup(): void
    {
        $dar = [['ugroup_id' => 100]];
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);
        $this->permissions_manager->expects(self::once())->method('getDao')->willReturn($dao);

        $dao->expects(self::once())->method('getDocmanAdminUgroups')->willReturn($dar);
        $dao->expects(self::never())->method('getUgroupMembers');
        self::assertEquals([], $this->permissions_manager->getDocmanAdminUsers($this->project));
    }

    public function testGetProjectAdminUsersError(): void
    {
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);
        $this->permissions_manager->method('getDao')->willReturn($dao);

        $dao->expects(self::once())->method('getProjectAdminMembers')->willReturn(null);
        self::assertEquals([], $this->permissions_manager->getProjectAdminUsers($this->project));
    }

    public function testGetProjectAdminUsersSuccess(): void
    {
        $dao = $this->createMock(Docman_PermissionsManagerDao::class);
        $dar = [
            [
                'email'       => 'john.doe@example.com',
                'language_id' => 'en_US',
            ],
            [
                'email'       => 'jane.doe@example.com',
                'language_id' => 'fr_FR',
            ],
        ];
        $this->permissions_manager->expects(self::once())->method('getDao')->willReturn($dao);

        $dao->expects(self::once())->method('getProjectAdminMembers')->willReturn($dar);
        $userArray = [
            'john.doe@example.com' => 'en_US',
            'jane.doe@example.com' => 'fr_FR',
        ];
        self::assertEquals($userArray, $this->permissions_manager->getProjectAdminUsers($this->project));
    }

    /**
     * @testWith [false, false]
     *           [true, true]
     */
    public function testWriterCanUpdateItemProperties(bool $forbid_writers_to_update, bool $expected): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(1234);
        $user->method('isAdmin');
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);

        $this->forbid_writers_settings->method('areWritersAllowedToUpdateProperties')->willReturn($forbid_writers_to_update);

        $item_id = 1515;

        $pm = $this->createMock(PermissionsManager::class);
        $pm->method('userHasPermission')->willReturnCallback(static fn(int $id, string $type, array $ugroups) => match (true) {
            $id === $item_id && $type === 'PLUGIN_DOCMAN_WRITE' && $ugroups === ['test'] => true,
            default                                                                      => false,
        });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        self::assertEquals(
            $expected,
            $this->permissions_manager->userCanUpdateItemProperties($user, new Docman_Item(['item_id' => $item_id]))
        );
    }
}
