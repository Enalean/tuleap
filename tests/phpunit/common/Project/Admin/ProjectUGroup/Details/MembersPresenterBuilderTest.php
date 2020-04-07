<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup\Details;

use Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Admin\ProjectUGroup\ProjectUGroupMemberUpdatable;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;

final class MembersPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var MembersPresenterBuilder
     */
    private $builder;
    /**
     * @var \EventManager|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var Mockery\MockInterface|\UserHelper
     */
    private $user_helper;
    /**
     * @var Mockery\MockInterface|SynchronizedProjectMembershipDetector
     */
    private $detector;

    protected function setUp(): void
    {
        $this->event_manager = Mockery::mock(\EventManager::class);
        $this->user_helper   = Mockery::mock(\UserHelper::class);
        $this->detector      = Mockery::mock(SynchronizedProjectMembershipDetector::class);
        $this->builder       = new MembersPresenterBuilder($this->event_manager, $this->user_helper, $this->detector);

        $this->detector
            ->shouldReceive('isSynchronizedWithProjectMembers')
            ->andReturnFalse()
            ->byDefault();

        $this->event_manager
            ->shouldReceive('processEvent')
            ->byDefault();
    }

    public function testItDoesNotAllowUpdatingBoundUGroups(): void
    {
        $ugroup = $this->getEmptyBoundStaticUGroup();

        $result = $this->builder->build($ugroup);

        $this->assertFalse($result->can_be_updated);
    }

    public function testItDoesNotAllowUpdatingUGroupsAccordingToEvent(): void
    {
        $ugroup = $this->getEmptyStaticUGroup();
        $this->event_manager
            ->shouldReceive('processEvent')
            ->with(Event::UGROUP_UPDATE_USERS_ALLOWED, Mockery::any())
            ->once()
            ->andReturnUsing(
                function (string $event_name, array $params): void {
                    $params['allowed'] = false;
                }
            );
        $this->event_manager
            ->shouldReceive('processEvent')
            ->with(Mockery::type(ProjectUGroupMemberUpdatable::class))
            ->once();

        $result = $this->builder->build($ugroup);

        $this->assertFalse($result->can_be_updated);
    }

    public function testItSetsTheUGroupAsDynamic(): void
    {
        $ugroup = Mockery::mock(
            \ProjectUGroup::class,
            ['isBound' => false, 'getId' => 98, 'isStatic' => false, 'getProject' => Mockery::mock(\Project::class)]
        );
        $ugroup->shouldReceive('getMembersIncludingSuspendedAndDeleted')
            ->andReturn([]);

        $result = $this->builder->build($ugroup);

        $this->assertTrue($result->is_dynamic_group);
    }

    public function testItSetsTheUGroupAsStatic(): void
    {
        $ugroup = $this->getEmptyStaticUGroup();

        $result = $this->builder->build($ugroup);

        $this->assertFalse($result->is_dynamic_group);
    }

    public function testItShowsTheSynchronizedMembersipMessageWhenUGroupCanBeUpdated(): void
    {
        $ugroup = $this->getEmptyStaticUGroup();
        $this->detector->shouldReceive('isSynchronizedWithProjectMembers')
            ->with($ugroup->getProject())
            ->once()
            ->andReturnTrue();

        $result = $this->builder->build($ugroup);

        $this->assertTrue($result->is_synchronized_message_shown);
    }

    public function testItDoesNotShowTheSynchronizedMembershipMessageWhenUGroupCannotBeUpdated(): void
    {
        $ugroup = $this->getEmptyBoundStaticUGroup();
        $this->detector->shouldReceive('isSynchronizedWithProjectMembers')
            ->once()
            ->andReturnTrue();

        $result = $this->builder->build($ugroup);

        $this->assertFalse($result->is_synchronized_message_shown);
    }

    public function testItDoesNotShowTheSynchronizedMembershipMessageWhenItIsNotEnabled(): void
    {
        $ugroup = $this->getEmptyStaticUGroup();
        $this->detector->shouldReceive('isSynchronizedWithProjectMembers')
            ->once()
            ->andReturnFalse();

        $result = $this->builder->build($ugroup);

        $this->assertFalse($result->is_synchronized_message_shown);
    }

    public function testItFormatsUGroupMembers(): void
    {
        $ugroup = Mockery::mock(
            \ProjectUGroup::class,
            ['isBound' => false, 'getId' => 98, 'isStatic' => true, 'getProject' => Mockery::mock(\Project::class), 'getProjectId' => 202]
        );
        $first_member = Mockery::mock(
            \PFUser::class,
            [
                'getUserName'  => 'jmost',
                'getId'        => 105,
                'getRealName'  => 'Junko Most',
                'hasAvatar'    => true,
                'isAdmin'      => false,
                'getStatus'    => 'A',
                'getAvatarUrl' => ''
            ]
        );
        $ugroup->shouldReceive('getMembersIncludingSuspendedAndDeleted')
            ->andReturn([$first_member]);
        $this->user_helper
            ->shouldReceive('getDisplayName')
            ->with('jmost', 'Junko Most')
            ->andReturn('Junko Most (jmost)');

        $result = $this->builder->build($ugroup);

        $first_formatted_member = $result->members[0];
        $this->assertSame('Junko Most (jmost)', $first_formatted_member->username_display);
        $this->assertSame('/users/jmost/', $first_formatted_member->profile_page_url);
        $this->assertTrue($first_formatted_member->has_avatar);
        $this->assertSame('jmost', $first_formatted_member->user_name);
        $this->assertSame(105, $first_formatted_member->user_id);
        $this->assertTrue($first_formatted_member->is_member_updatable);
        $this->assertEmpty($first_formatted_member->member_updatable_messages);
        $this->assertFalse($first_formatted_member->is_news_admin);
        $this->assertSame(0, $first_formatted_member->user_is_project_admin);
    }

    public function testItSetsMembersAsNotUpdatableAccordingToEvent(): void
    {
        $ugroup = Mockery::mock(
            \ProjectUGroup::class,
            ['isBound' => false, 'getId' => 98, 'isStatic' => true, 'getProject' => Mockery::mock(\Project::class), 'getProjectId' => 202]
        );
        $this->event_manager
            ->shouldReceive('processEvent')
            ->with(Event::UGROUP_UPDATE_USERS_ALLOWED, Mockery::any())
            ->once();
        $first_member = Mockery::mock(
            \PFUser::class,
            [
                'getUserName'  => 'jmost',
                'getId'        => 105,
                'getRealName'  => 'Junko Most',
                'hasAvatar'    => true,
                'isAdmin'      => false,
                'getStatus'    => 'A',
                'getAvatarUrl' => ''
            ]
        );
        $ugroup->shouldReceive('getMembersIncludingSuspendedAndDeleted')
            ->andReturn([$first_member]);

        $this->event_manager
            ->shouldReceive('processEvent')
            ->with(Mockery::type(ProjectUGroupMemberUpdatable::class))
            ->once()
            ->andReturnUsing(
                function (ProjectUGroupMemberUpdatable $event) use ($first_member): void {
                    $event->markUserHasNotUpdatable($first_member, 'User cannot be updated');
                }
            );
        $this->user_helper->shouldReceive('getDisplayName');

        $result = $this->builder->build($ugroup);

        $first_formatted_member = $result->members[0];
        $this->assertFalse($first_formatted_member->is_member_updatable);
        $this->assertContains('User cannot be updated', $first_formatted_member->member_updatable_messages);
    }

    public function testItSetsMemberIsNewsAdmin(): void
    {
        $ugroup = Mockery::mock(
            \ProjectUGroup::class,
            [
                'isBound'      => false,
                'getId'        => \ProjectUGroup::NEWS_WRITER,
                'getProjectId' => 180,
                'isStatic'     => false,
                'getProject'   => Mockery::mock(\Project::class),
            ]
        );
        $first_member = Mockery::mock(
            \PFUser::class,
            [
                'getUserName'  => 'jmost',
                'getId'        => 105,
                'getRealName'  => 'Junko Most',
                'hasAvatar'    => true,
                'isAdmin'      => false,
                'getStatus'    => 'A',
                'getAvatarUrl' => ''
            ]
        );
        $first_member->shouldReceive('isMember')
            ->with(180, 'N2')
            ->andReturnTrue();
        $ugroup->shouldReceive('getMembersIncludingSuspendedAndDeleted')
            ->andReturn([$first_member]);
        $this->user_helper->shouldReceive('getDisplayName');

        $result = $this->builder->build($ugroup);

        $first_formatted_member = $result->members[0];
        $this->assertTrue($first_formatted_member->is_news_admin);
    }

    private function getEmptyBoundStaticUGroup(): \ProjectUGroup
    {
        $ugroup = Mockery::mock(
            \ProjectUGroup::class,
            ['isBound' => true, 'getId' => 98, 'isStatic' => true, 'getProject' => Mockery::mock(\Project::class)]
        );
        $ugroup->shouldReceive('getMembersIncludingSuspendedAndDeleted')
            ->andReturn([]);
        return $ugroup;
    }

    private function getEmptyStaticUGroup(): \ProjectUGroup
    {
        $ugroup = Mockery::mock(
            \ProjectUGroup::class,
            ['isBound' => false, 'getId' => 98, 'isStatic' => true, 'getProject' => Mockery::mock(\Project::class)]
        );
        $ugroup->shouldReceive('getMembersIncludingSuspendedAndDeleted')
            ->andReturn([]);
        return $ugroup;
    }
}
