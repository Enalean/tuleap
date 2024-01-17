<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVNCore;

use Tuleap\Project\UGroupRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;
use Tuleap\Test\Stubs\UGroupRetrieverStub;

final class SVNAccessFileDefaultBlockGeneratorTest extends TestCase
{
    /**
     * @dataProvider membersDataProvider
     */
    public function testMembers(UGroupRetriever $user_group_retriever, callable $plugin, string $expected): void
    {
        $event_manager = new \EventManager();
        $event_manager->addListener(SVNAccessFileDefaultBlockOverride::class, null, $plugin, false);

        $generator = new SVNAccessFileDefaultBlockGenerator(
            $user_group_retriever,
            CheckProjectAccessStub::withValidAccess(),
            $event_manager,
        );
        self::assertEquals(
            new SVNAccessFileDefaultBlock(
                <<<EOT
                [groups]
                $expected

                [/]
                * = r
                @members = rw

                EOT
            ),
            $generator->getDefaultBlock(ProjectTestBuilder::aProject()->build())
        );
    }

    public function membersDataProvider(): iterable
    {
        $jmalko    = UserTestBuilder::anActiveUser()->withId(120)->withUserName('jmalko')->build();
        $csteven   = UserTestBuilder::anActiveUser()->withId(121)->withUserName('csteven')->build();
        $disciplus = UserTestBuilder::anActiveUser()->withId(122)->withUserName('disciplus_simplex')->build();

        $default_user_groups = UGroupRetrieverStub::buildWithUserGroups(
            ProjectUGroupTestBuilder::buildProjectMembersWith($jmalko, $csteven, $disciplus),
            ProjectUGroupTestBuilder::buildProjectAdmins(),
            ProjectUGroupTestBuilder::aCustomUserGroup(230)->withName('Developers')->withUsers($csteven, $disciplus)->build()
        );

        $noop_plugin = fn (SVNAccessFileDefaultBlockOverride $event) => 1;

        return [
            'Nominal case' => [
                'user_groups' => $default_user_groups,
                'plugin'  => $noop_plugin,
                'expected' => <<<EOT
                    members = jmalko, csteven, disciplus_simplex
                    Developers = csteven, disciplus_simplex
                    EOT,
            ],
            'Plugin overrides name generator' => [
                'user_groups' => $default_user_groups,
                'plugin'  => function (SVNAccessFileDefaultBlockOverride $event) {
                    foreach ($event->user_groups as $user_group) {
                        $event->addSVNGroup(
                            SVNUserGroup::fromUserGroupAndMembers(
                                $user_group,
                                ...array_filter(
                                    array_map(
                                        fn(\PFUser $user) => match ($user->getUserName()) {
                                            'jmalko' => new SVNUser($user, 'jm256'),
                                            'csteven' => new SVNUser($user, 'cs257'),
                                            'disciplus_simplex' => null,
                                        },
                                        $user_group->getMembers()
                                    )
                                )
                            )
                        );
                    }
                },
                'expected' => <<<EOT
                    members = jm256, cs257
                    Developers = cs257
                    EOT,
            ],
            'Project members (members) is the only allowed user group' => [
                'user_groups' => UGroupRetrieverStub::buildWithUserGroups(
                    ProjectUGroupTestBuilder::buildAnonymous(),
                    ProjectUGroupTestBuilder::buildRegistered(),
                    ProjectUGroupTestBuilder::buildProjectMembersWith($jmalko, $csteven, $disciplus),
                    ProjectUGroupTestBuilder::buildProjectAdmins(),
                    ProjectUGroupTestBuilder::buildNobody(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(230)->withName('Developers')->withUsers($csteven, $disciplus)->build()
                ),
                'plugin'  => $noop_plugin,
                'expected' => <<<EOT
                    members = jmalko, csteven, disciplus_simplex
                    Developers = csteven, disciplus_simplex
                    EOT,
            ],
            'Empty user group should not be present' => [
                'user_groups' => UGroupRetrieverStub::buildWithUserGroups(
                    ProjectUGroupTestBuilder::buildProjectMembersWith($jmalko, $csteven, $disciplus),
                    ProjectUGroupTestBuilder::aCustomUserGroup(230)->withName('Developers')->withoutUsers()->build()
                ),
                'plugin'  => $noop_plugin,
                'expected' => <<<EOT
                    members = jmalko, csteven, disciplus_simplex
                    EOT,
            ],
        ];
    }

    /**
     * @dataProvider permissionsDataProvider
     */
    public function testPermissions(\Project $project, callable $plugin, string $expected): void
    {
        $event_manager = new \EventManager();
        $event_manager->addListener(SVNAccessFileDefaultBlockOverride::class, null, $plugin, false);

        $jmalko    = UserTestBuilder::anActiveUser()->withId(120)->withUserName('jmalko')->build();
        $csteven   = UserTestBuilder::anActiveUser()->withId(121)->withUserName('csteven')->build();
        $disciplus = UserTestBuilder::anActiveUser()->withId(122)->withUserName('disciplus_simplex')->build();

        $default_user_groups = UGroupRetrieverStub::buildWithUserGroups(
            ProjectUGroupTestBuilder::buildProjectMembersWith($jmalko, $csteven, $disciplus),
            ProjectUGroupTestBuilder::buildProjectAdmins(),
            ProjectUGroupTestBuilder::aCustomUserGroup(230)->withName('Developers')->withUsers($csteven, $disciplus)->build()
        );

        $generator = new SVNAccessFileDefaultBlockGenerator(
            $default_user_groups,
            CheckProjectAccessStub::withValidAccess(),
            $event_manager,
        );
        self::assertEquals(
            new SVNAccessFileDefaultBlock(
                <<<EOT
                [groups]
                members = jmalko, csteven, disciplus_simplex
                Developers = csteven, disciplus_simplex

                [/]
                $expected
                @members = rw

                EOT
            ),
            $generator->getDefaultBlock($project)
        );
    }

    public function permissionsDataProvider(): iterable
    {
        $noop_plugin    = fn (SVNAccessFileDefaultBlockOverride $event) => 1;
        $public_project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        return [
            'Despite public project, plugin forbid access' => [
                'project' => $public_project,
                'plugin'  => fn (SVNAccessFileDefaultBlockOverride $event) => $event->disableWorldAccess(),
                'expected' => '* =',
            ],
            'Public projects are world readable' => [
                'project' => $public_project,
                'plugin'  => $noop_plugin,
                'expected' => '* = r',
            ],
            'Private project means no access' => [
                'project' => ProjectTestBuilder::aProject()->withAccessPrivate()->build(),
                'plugin'  => $noop_plugin,
                'expected' => '* =',
            ],
        ];
    }
}
