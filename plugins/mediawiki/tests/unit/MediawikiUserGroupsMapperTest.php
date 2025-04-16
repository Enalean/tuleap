<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Mediawiki;

use MediawikiDao;
use MediawikiUserGroupsMapper;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use TestHelper;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use User_ForgeUserGroupPermissionsDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediawikiUserGroupsMapperTest extends TestCase
{
    private MediawikiDao&MockObject $dao;

    private User_ForgeUserGroupPermissionsDao&MockObject $forge_perms_dao;

    private MediawikiUserGroupsMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao             = $this->createMock(MediawikiDao::class);
        $this->forge_perms_dao = $this->createMock(User_ForgeUserGroupPermissionsDao::class);

        $this->dao->method('resetUserGroups');
        $this->forge_perms_dao->method('doesUserHavePermission');

        $this->mapper = new MediawikiUserGroupsMapper($this->dao, $this->forge_perms_dao);
    }

    public function testItAddsProjectMembersAsBots(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->dao->method('getMediawikiUserGroupMapping')->willReturn([
            ['group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS],
        ]);

        $new_mapping = [
            'anonymous'  => [
                '1',
            ],
            'bot'        => [
                '3',
            ],
            'user'       => [],
            'sysop'      => [],
            'bureaucrat' => [],
        ];

        $this->dao
            ->expects($this->once())
            ->method('addMediawikiUserGroupMapping')
            ->with($project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT, '3');

        $this->dao->expects($this->never())->method('removeMediawikiUserGroupMapping');

        $this->mapper->saveMapping($new_mapping, $project);
    }

    public function testItRemovesRegisteredUsersFromBot(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->dao->method('getMediawikiUserGroupMapping')->willReturn([
            ['group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS],
            ['group_id' => '104', 'ugroup_id' => '2', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER],
            ['group_id' => '104', 'ugroup_id' => '3', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT],
        ]);

        $new_mapping = [
            'anonymous'  => [
                '1',
            ],
            'user'       => [
                '2',
            ],
            'bot'        => [],
            'sysop'      => [],
            'bureaucrat' => [],
        ];

        $this->dao->expects($this->once())
            ->method('removeMediawikiUserGroupMapping')
            ->with($project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT, '3');

        $this->dao->expects($this->never())->method('addMediawikiUserGroupMapping');

        $this->mapper->saveMapping($new_mapping, $project);
    }

    public function testItIgnoresAnonymousModifications(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->dao->method('getMediawikiUserGroupMapping')->willReturn([
            ['group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS],
        ]);

        $new_mapping = [
            'anonymous'  => [],
            'bot'        => [],
            'user'       => [],
            'sysop'      => [],
            'bureaucrat' => [],
        ];

        $this->dao->expects($this->never())->method('removeMediawikiUserGroupMapping');
        $this->dao->expects($this->never())->method('addMediawikiUserGroupMapping');

        $this->mapper->saveMapping($new_mapping, $project);
    }

    public function testItIgnoresUserModifications(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->dao->method('getMediawikiUserGroupMapping')->willReturn([
            ['group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS],
            ['group_id' => '104', 'ugroup_id' => '2', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER],
        ]);

        $new_mapping = [
            'anonymous'  => [],
            'bot'        => [],
            'user'       => [],
            'sysop'      => [],
            'bureaucrat' => [],
        ];

        $this->dao->expects($this->never())->method('removeMediawikiUserGroupMapping');
        $this->dao->expects($this->never())->method('addMediawikiUserGroupMapping');

        $this->mapper->saveMapping($new_mapping, $project);
    }

    public function testItCallsRemoveAndAddDAOMethodsDuringSave(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->dao->method('getMediawikiUserGroupMapping')->willReturn([
            [
                'group_id'      => 104,
                'ugroup_id'     => 1,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 2,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT,
            ],
        ]);

        $new_mapping = [
            'anonymous'  => [
                '1',
            ],
            'user'       => [
                '2',
            ],
            'bot'        => [
                '3',
                '2',
                '4',
            ],
            'sysop'      => [
                '1',
            ],
            'bureaucrat' => [
                '1',
            ],
        ];

        $this->dao->expects($this->exactly(2))
            ->method('removeMediawikiUserGroupMapping')
            ->willReturnCallback(static fn(Project $called_project, string $unchecked_mw_group_name, int $unchecked_ugroup_id) => match (true) {
                $called_project === $project && $unchecked_mw_group_name === MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP && $unchecked_ugroup_id === 4,
                $called_project === $project && $unchecked_mw_group_name === MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT && $unchecked_ugroup_id === 4 => true
            });

        $this->dao->expects($this->exactly(5))->method('addMediawikiUserGroupMapping');

        $this->mapper->saveMapping($new_mapping, $project);
    }

    public function testItReturnsTrueIfCurrentMappingEqualsDefaultOneForPublicProject(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $current_mapping =  [
            [
                'group_id'      => 104,
                'ugroup_id'     => 1,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 2,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 3,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT,
            ],
        ];

        $this->dao->method('getMediawikiUserGroupMapping')->willReturn($current_mapping);

        $is_default = $this->mapper->isDefaultMapping($project);
        $this->assertTrue($is_default);
    }

    public function testItReturnsFalseIfCurrentMappingNotEqualsDefaultOneForPublicProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();

        $current_mapping =  [
            [
                'group_id'      => 104,
                'ugroup_id'     => 1,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 2,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT,
            ],
        ];

        $this->dao->method('getMediawikiUserGroupMapping')->willReturn($current_mapping);

        $is_default = $this->mapper->isDefaultMapping($project);
        $this->assertFalse($is_default);
    }

    public function testItReturnsTrueIfCurrentMappingEqualsDefaultOneForPrivateProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withAccessPrivate()->build();

        $current_mapping =  [
            [
                'group_id'      => 104,
                'ugroup_id'     => 3,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT,
            ],
        ];

        $this->dao->method('getMediawikiUserGroupMapping')->willReturn($current_mapping);

        $is_default = $this->mapper->isDefaultMapping($project);
        $this->assertTrue($is_default);
    }

    public function testItReturnsFalseIfCurrentMappingNotEqualsDefaultOneForPrivateProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withAccessPrivate()->build();

        $current_mapping =  [
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT,
            ],
            [
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT,
            ],
        ];

        $this->dao->method('getMediawikiUserGroupMapping')->willReturn($current_mapping);

        $is_default = $this->mapper->isDefaultMapping($project);
        $this->assertFalse($is_default);
    }

    public function testItReturnsRightMediawikiGroupsFromDatabase(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $this->dao->method('getMediawikiGroupsForUser')
            ->with($user, $project)
            ->willReturn(TestHelper::emptyDar());

        $this->dao->method('getMediawikiGroupsMappedForUGroups')
            ->with($user, $project)
            ->willReturn([
                ['real_name' => 'sysop'],
                ['real_name' => 'bureaucrat'],
            ]);

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($user, $project);

        $this->assertEquals($mediawiki_groups, [
            'added' => [
                '*',
                'sysop',
                'bureaucrat',
            ],
            'removed' => [
            ],
        ]);
    }

    public function testItSetsAnonymousUsersAsAnonymous(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::anAnonymousUser()->build();

        $this->dao->method('getMediawikiGroupsForUser')
            ->with($user, $project)
            ->willReturn(TestHelper::emptyDar());

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($user, $project);

        $this->assertEquals($mediawiki_groups, [
            'added' => [
                '*',
            ],
            'removed' => [
            ],
        ]);
    }

    public function testItSetsAnonymousWhenNothingIsAvailable(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $this->dao->method('getMediawikiGroupsForUser')
            ->with($user, $project)
            ->willReturn(TestHelper::emptyDar());

        $this->dao->method('getMediawikiGroupsMappedForUGroups')
            ->with($user, $project)
            ->willReturn(TestHelper::emptyDar());

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($user, $project);

        $this->assertEquals($mediawiki_groups, [
            'added' => [
                '*',
            ],
            'removed' => [
            ],
        ]);
    }

    public function testItReturnsUnconsistantMediawikiGroupsToBeDeleted(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $this->dao->method('getMediawikiGroupsMappedForUGroups')
            ->with($user, $project)
            ->willReturn(TestHelper::emptyDar());

        $groups_dar = $this->createStub(LegacyDataAccessResultInterface::class);
        $this->dao->method('getMediawikiGroupsForUser')
            ->with($user, $project)
            ->willReturn($groups_dar);

        $first_row = ['ug_group' => 'ForgeRole:forge_admin'];
        $groups_dar->method('valid')->willReturn(true, false);
        $groups_dar->method('current')->willReturn($first_row);
        $groups_dar->method('rewind');
        $groups_dar->method('next');

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($user, $project);

        $this->assertEquals(['ForgeRole:forge_admin'], $mediawiki_groups['removed']);
    }

    public function testItRevokesGroupsTheUserIsNoLongerMemberOf(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $groups_dar = $this->createStub(LegacyDataAccessResultInterface::class);
        $this->dao->method('getMediawikiGroupsForUser')
            ->with($user, $project)
            ->willReturn($groups_dar);

        $first_row = ['ug_group' => 'bureaucrat'];
        $groups_dar->method('valid')->willReturn(true, false);
        $groups_dar->method('current')->willReturn($first_row);
        $groups_dar->method('rewind');
        $groups_dar->method('next');

        $this->dao->method('getMediawikiGroupsMappedForUGroups')
            ->with($user, $project)
            ->willReturn(TestHelper::emptyDar());

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($user, $project);

        $this->assertEquals($mediawiki_groups['removed'], ['bureaucrat']);
    }

    public function testItDoesNotAddGroupsTheUserAlreadyHave(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $groups_dar = $this->createStub(LegacyDataAccessResultInterface::class);
        $this->dao->method('getMediawikiGroupsForUser')
            ->with($user, $project)
            ->willReturn($groups_dar);

        $first_row = ['ug_group' => '*'];
        $groups_dar->method('valid')->willReturn(true, false);
        $groups_dar->method('current')->willReturn($first_row);
        $groups_dar->method('rewind');
        $groups_dar->method('next');

        $mapped_dar = $this->createStub(LegacyDataAccessResultInterface::class);
        $this->dao->method('getMediawikiGroupsMappedForUGroups')
            ->with($user, $project)
            ->willReturn($mapped_dar);

        $first_row = ['real_name' => '*'];
        $mapped_dar->method('valid')->willReturn(true, false);
        $mapped_dar->method('current')->willReturn($first_row);
        $mapped_dar->method('rewind');
        $mapped_dar->method('next');

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($user, $project);

        $this->assertEquals($mediawiki_groups, ['added' => [], 'removed' => []]);
    }
}
