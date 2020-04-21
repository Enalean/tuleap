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

use MediawikiUserGroupsMapper;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

require_once 'bootstrap.php';

final class MediawikiUserGroupsMapperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $tuleap_user;

    /**
     * @var \MediawikiDao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\User_ForgeUserGroupPermissionsDao
     */
    private $forge_perms_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;

    /**
     * @var MediawikiUserGroupsMapper
     */
    private $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tuleap_user     = Mockery::mock(\PFUser::class);
        $this->tuleap_user->shouldReceive('getId')->andReturn(101);

        $this->dao             = \Mockery::spy(\MediawikiDao::class);
        $this->forge_perms_dao = \Mockery::spy(\User_ForgeUserGroupPermissionsDao::class);
        $this->project         = \Mockery::spy(\Project::class);

        $this->mapper = new MediawikiUserGroupsMapper($this->dao, $this->forge_perms_dao);
    }

    public function testItAddsProjectMembersAsBots(): void
    {
        $this->dao->shouldReceive('getMediawikiUserGroupMapping')->andReturn([
            array('group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS)
        ]);

        $new_mapping = array(
            'anonymous'  => array(
                '1',
            ),
            'bot'        => array(
                '3'
            ),
            'user'       => array(),
            'sysop'      => array(),
            'bureaucrat' => array()
        );

        $this->dao->shouldReceive('addMediawikiUserGroupMapping')
            ->with($this->project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT, '3')
            ->once();

        $this->dao->shouldReceive('removeMediawikiUserGroupMapping')->never();

        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function testItRemovesRegisteredUsersFromBot(): void
    {
        $this->dao->shouldReceive('getMediawikiUserGroupMapping')->andReturn([
            array('group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS),
            array('group_id' => '104', 'ugroup_id' => '2', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER),
            array('group_id' => '104', 'ugroup_id' => '3', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT)
        ]);

        $new_mapping = array(
            'anonymous'  => array(
                '1'
            ),
            'user'       => array(
                '2'
            ),
            'bot'        => array(),
            'sysop'      => array(),
            'bureaucrat' => array(),
        );

        $this->dao->shouldReceive('removeMediawikiUserGroupMapping')
            ->with($this->project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT, '3')
            ->once();

        $this->dao->shouldReceive('addMediawikiUserGroupMapping')->never();

        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function testItIgnoresAnonymousModifications(): void
    {
        $this->dao->shouldReceive('getMediawikiUserGroupMapping')->andReturn([
            array('group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS)
        ]);

        $new_mapping = array(
            'anonymous'  => array(),
            'bot'        => array(),
            'user'       => array(),
            'sysop'      => array(),
            'bureaucrat' => array()
        );

        $this->dao->shouldReceive('removeMediawikiUserGroupMapping')->never();
        $this->dao->shouldReceive('addMediawikiUserGroupMapping')->never();

        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function testItIgnoresUserModifications(): void
    {
        $this->dao->shouldReceive('getMediawikiUserGroupMapping')->andReturn([
            array('group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS),
            array('group_id' => '104', 'ugroup_id' => '2', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER)
        ]);

        $new_mapping = array(
            'anonymous'  => array(),
            'bot'        => array(),
            'user'       => array(),
            'sysop'      => array(),
            'bureaucrat' => array()
        );

        $this->dao->shouldReceive('removeMediawikiUserGroupMapping')->never();
        $this->dao->shouldReceive('addMediawikiUserGroupMapping')->never();

        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function testItCallsRemoveAndAddDAOMethodsDuringSave(): void
    {
        $this->dao->shouldReceive('getMediawikiUserGroupMapping')->andReturn([
            array(
                'group_id'      => 104,
                'ugroup_id'     => 1,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 2,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        ]);

        $new_mapping = array(
            'anonymous'  => array(
                '1'
            ),
            'user'       => array(
                '2',
            ),
            'bot'        => array(
                '3',
                '2',
                '4'
            ),
            'sysop'      => array(
                '1'
            ),
            'bureaucrat' => array(
                '1'
            )
        );

        $this->dao->shouldReceive('removeMediawikiUserGroupMapping')
            ->with($this->project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP, 4)
            ->once();

        $this->dao->shouldReceive('removeMediawikiUserGroupMapping')
            ->with($this->project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT, 4)
            ->once();

        $this->dao->shouldReceive('addMediawikiUserGroupMapping')->times(5);

        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function testItReturnsTrueIfCurrentMappingEqualsDefaultOneForPublicProject(): void
    {
        $current_mapping = array (
            array(
                'group_id'      => 104,
                'ugroup_id'     => 1,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 2,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 3,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        );

        $this->dao->shouldReceive('getMediawikiUserGroupMapping')->andReturn($current_mapping);
        $this->project->shouldReceive('isPublic')->andReturnTrue();

        $is_default = $this->mapper->isDefaultMapping($this->project);
        $this->assertTrue($is_default);
    }

    public function testItReturnsFalseIfCurrentMappingNotEqualsDefaultOneForPublicProject(): void
    {
        $current_mapping = array (
            array(
                'group_id'      => 104,
                'ugroup_id'     => 1,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 2,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        );

        $this->dao->shouldReceive('getMediawikiUserGroupMapping')->andReturn($current_mapping);
        $this->project->shouldReceive('isPublic')->andReturnTrue();

        $is_default = $this->mapper->isDefaultMapping($this->project);
        $this->assertFalse($is_default);
    }

    public function testItReturnsTrueIfCurrentMappingEqualsDefaultOneForPrivateProject(): void
    {
        $current_mapping = array (
            array(
                'group_id'      => 104,
                'ugroup_id'     => 3,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        );

        $this->dao->shouldReceive('getMediawikiUserGroupMapping')->andReturn($current_mapping);
        $this->project->shouldReceive('isPublic')->andReturnFalse();

        $is_default = $this->mapper->isDefaultMapping($this->project);
        $this->assertTrue($is_default);
    }

    public function testItReturnsFalseIfCurrentMappingNotEqualsDefaultOneForPrivateProject(): void
    {
        $current_mapping = array (
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        );

        $this->dao->shouldReceive('getMediawikiUserGroupMapping')->andReturn($current_mapping);
        $this->project->shouldReceive('isPublic')->andReturnFalse();

        $is_default = $this->mapper->isDefaultMapping($this->project);
        $this->assertFalse($is_default);
    }

    public function testItReturnsRightMediawikiGroupsFromDatabase(): void
    {
        $dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsForUser')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($dar);

        $this->dao->shouldReceive('getMediawikiGroupsMappedForUGroups')
            ->with($this->tuleap_user, $this->project)
            ->andReturn([
                array('real_name' => 'sysop'),
                array('real_name' => 'bureaucrat')
            ]);

        $this->tuleap_user->shouldReceive('isAnonymous')->andReturnFalse();

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEquals($mediawiki_groups, array(
            'added' => array(
                '*',
                'sysop',
                'bureaucrat'
            ),
            'removed' => array(
            )
        ));
    }

    public function testItSetsAnonymousUsersAsAnonymous(): void
    {
        $this->tuleap_user->shouldReceive('isAnonymous')->andReturnTrue();
        $dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsForUser')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($dar);

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEquals($mediawiki_groups, array(
            'added' => array(
                '*',
            ),
            'removed' => array(
            )
        ));
    }

    public function testItSetsAnonymousWhenNothingIsAvailable(): void
    {
        $dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsForUser')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($dar);

        $dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsMappedForUGroups')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($dar);

        $this->tuleap_user->shouldReceive('isAnonymous')->andReturnFalse();

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEquals($mediawiki_groups, array(
            'added' => array(
                '*',
            ),
            'removed' => array(
            )
        ));
    }

    public function testItReturnsUnconsistantMediawikiGroupsToBeDeleted(): void
    {
        $this->tuleap_user->shouldReceive('isAnonymous')->andReturnFalse();
        $this->tuleap_user->shouldReceive('isMember')->with(202, 'A')->andReturnTrue();

        $dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsMappedForUGroups')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($dar);

        $groups_dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsForUser')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($groups_dar);

        $first_row = array('ug_group' => 'ForgeRole:forge_admin');
        $groups_dar->shouldReceive('valid')->andReturn(true, false);
        $groups_dar->shouldReceive('current')->andReturn($first_row);

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEquals(array('ForgeRole:forge_admin'), $mediawiki_groups['removed']);
    }

    public function testItRevokesGroupsTheUserIsNoLongerMemberOf(): void
    {
        $this->tuleap_user->shouldReceive('isAnonymous')->andReturnFalse();
        $this->tuleap_user->shouldReceive('isMember')->with(202, 'A')->andReturnTrue();

        $groups_dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsForUser')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($groups_dar);

        $first_row = array('ug_group' => 'bureaucrat');
        $groups_dar->shouldReceive('valid')->andReturn(true, false);
        $groups_dar->shouldReceive('current')->andReturn($first_row);

        $dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsMappedForUGroups')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($dar);

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEquals($mediawiki_groups['removed'], array('bureaucrat'));
    }

    public function testItDoesNotAddGroupsTheUserAlreadyHave(): void
    {
        $this->tuleap_user->shouldReceive('isAnonymous')->andReturnFalse();
        $this->tuleap_user->shouldReceive('isMember')->with(202, 'A')->andReturnTrue();

        $groups_dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsForUser')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($groups_dar);

        $first_row = array('ug_group' => '*');
        $groups_dar->shouldReceive('valid')->andReturn(true, false);
        $groups_dar->shouldReceive('current')->andReturn($first_row);

        $mapped_dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->dao->shouldReceive('getMediawikiGroupsMappedForUGroups')
            ->with($this->tuleap_user, $this->project)
            ->andReturn($mapped_dar);

        $first_row = array('real_name' => '*');
        $mapped_dar->shouldReceive('valid')->andReturn(true, false);
        $mapped_dar->shouldReceive('current')->andReturn($first_row);

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEquals($mediawiki_groups, array('added' => array(), 'removed' => array()));
    }
}
