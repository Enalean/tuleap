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

use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BindValueLabelProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsFromStaticBindValue(): void
    {
        $bind_value = ListStaticValueBuilder::aStaticValue('Planned')->withId(3860)->build();
        $label      = BindValueLabelProxy::fromListBindValue($bind_value);
        self::assertSame('Planned', $label->getLabel());
    }

    public function testItBuildsFromUsersBindValue(): void
    {
        $bind_value = ListUserValueBuilder::aUserWithId(110)->withDisplayedName('Darrick Mahomly')->build();
        $label      = BindValueLabelProxy::fromListBindValue($bind_value);
        self::assertSame('Darrick Mahomly', $label->getLabel());
    }

    public function testItBuildsFromUserGroupsBindValue(): void
    {
        $ugroup     = new \ProjectUGroup([
            'ugroup_id' => \ProjectUGroup::PROJECT_MEMBERS,
            'name'      => \ProjectUGroup::NORMALIZED_NAMES[\ProjectUGroup::PROJECT_MEMBERS],
        ]);
        $bind_value = ListUserGroupValueBuilder::aUserGroupValue($ugroup)->withId(492)->build();
        $label      = BindValueLabelProxy::fromListBindValue($bind_value);
        self::assertSame('project_members', $label->getLabel());
    }
}
