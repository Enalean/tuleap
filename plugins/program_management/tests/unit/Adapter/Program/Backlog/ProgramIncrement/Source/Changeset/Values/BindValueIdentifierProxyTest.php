<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BindValueIdentifierProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsFromStaticBindValue(): void
    {
        $bind_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            '3860',
            'Planned',
            'A Planned Progam Increment',
            0,
            false
        );
        $identifier = BindValueIdentifierProxy::fromListBindValue($bind_value);
        self::assertSame(3860, $identifier->getId());
    }

    public function testItBuildsFromUsersBindValue(): void
    {
        $bind_value = new \Tracker_FormElement_Field_List_Bind_UsersValue(110, 'dmahomly', 'Darrick Mahomly');
        $identifier = BindValueIdentifierProxy::fromListBindValue($bind_value);
        self::assertSame(110, $identifier->getId());
    }

    public function testItBuildsFromUserGroupsBindValue(): void
    {
        $ugroup     = new \ProjectUGroup([
            'ugroup_id' => \ProjectUGroup::PROJECT_MEMBERS,
            'name'      => \ProjectUGroup::NORMALIZED_NAMES[\ProjectUGroup::PROJECT_MEMBERS],
        ]);
        $bind_value = new \Tracker_FormElement_Field_List_Bind_UgroupsValue(492, $ugroup, false);
        $identifier = BindValueIdentifierProxy::fromListBindValue($bind_value);
        self::assertSame(492, $identifier->getId());
    }
}
