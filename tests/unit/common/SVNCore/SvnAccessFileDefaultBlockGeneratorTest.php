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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;
use Tuleap\Test\Stubs\UGroupRetrieverStub;

final class SvnAccessFileDefaultBlockGeneratorTest extends TestCase
{
    public function testReferenceUsage(): void
    {
        $jmalko    = UserTestBuilder::anActiveUser()->withId(120)->withUserName('jmalko')->build();
        $csteven   = UserTestBuilder::anActiveUser()->withId(121)->withUserName('csteven')->build();
        $disciplus = UserTestBuilder::anActiveUser()->withId(122)->withUserName('disciplus_simplex')->build();
        $generator = new SvnAccessFileDefaultBlockGenerator(
            UGroupRetrieverStub::buildWithUserGroups(
                ProjectUGroupTestBuilder::buildProjectMembersWith($jmalko, $csteven, $disciplus),
                ProjectUGroupTestBuilder::buildProjectAdmins(),
                ProjectUGroupTestBuilder::aCustomUserGroup(230)->withName('Developers')->withUsers($csteven, $disciplus)->build()
            ),
            CheckProjectAccessStub::withValidAccess(),
            new \EventManager(),
        );
        self::assertEquals(
            <<<EOT
            [groups]
            members = jmalko, csteven, disciplus_simplex
            Developers = csteven, disciplus_simplex

            [/]
            * = r
            @members = rw

            EOT,
            $generator->getDefaultBlock(ProjectTestBuilder::aProject()->build())
        );
    }

    public function testPluginOverridesDefaultGroupMembers(): void
    {
        $jmalko    = UserTestBuilder::anActiveUser()->withId(120)->withUserName('jmalko')->build();
        $csteven   = UserTestBuilder::anActiveUser()->withId(121)->withUserName('csteven')->build();
        $disciplus = UserTestBuilder::anActiveUser()->withId(122)->withUserName('disciplus_simplex')->build();

        $event_manager = new \EventManager();
        $event_manager->addListener(GetSVNUserGroups::class, null, function (GetSVNUserGroups $event) {
            foreach ($event->user_groups as $user_group) {
                $event->addSVNGroup(
                    SVNUserGroup::fromUserGroupAndMembers(
                        $user_group,
                        ...array_filter(
                            array_map(
                                fn (\PFUser $user) => match ($user->getUserName()) {
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
        }, false);


        $generator = new SvnAccessFileDefaultBlockGenerator(
            UGroupRetrieverStub::buildWithUserGroups(
                ProjectUGroupTestBuilder::buildProjectMembersWith($jmalko, $csteven, $disciplus),
                ProjectUGroupTestBuilder::buildProjectAdmins(),
                ProjectUGroupTestBuilder::aCustomUserGroup(230)->withName('Developers')->withUsers($csteven, $disciplus)->build()
            ),
            CheckProjectAccessStub::withValidAccess(),
            $event_manager,
        );
        self::assertEquals(
            <<<EOT
            [groups]
            members = jm256, cs257
            Developers = cs257

            [/]
            * = r
            @members = rw

            EOT,
            $generator->getDefaultBlock(ProjectTestBuilder::aProject()->build())
        );
    }

    public function testProjectMembersIsTheOnlyAllowedDynamicUserGroup(): void
    {
        $jmalko    = UserTestBuilder::anActiveUser()->withId(120)->withUserName('jmalko')->build();
        $csteven   = UserTestBuilder::anActiveUser()->withId(121)->withUserName('csteven')->build();
        $disciplus = UserTestBuilder::anActiveUser()->withId(122)->withUserName('disciplus_simplex')->build();
        $generator = new SvnAccessFileDefaultBlockGenerator(
            UGroupRetrieverStub::buildWithUserGroups(
                ProjectUGroupTestBuilder::buildAnonymous(),
                ProjectUGroupTestBuilder::buildRegistered(),
                ProjectUGroupTestBuilder::buildProjectMembersWith($jmalko, $csteven, $disciplus),
                ProjectUGroupTestBuilder::buildProjectAdmins(),
                ProjectUGroupTestBuilder::buildNobody(),
                ProjectUGroupTestBuilder::aCustomUserGroup(230)->withName('Developers')->withUsers($csteven, $disciplus)->build()
            ),
            CheckProjectAccessStub::withValidAccess(),
            new \EventManager(),
        );
        self::assertEquals(
            <<<EOT
            [groups]
            members = jmalko, csteven, disciplus_simplex
            Developers = csteven, disciplus_simplex

            [/]
            * = r
            @members = rw

            EOT,
            $generator->getDefaultBlock(ProjectTestBuilder::aProject()->build())
        );
    }
}
