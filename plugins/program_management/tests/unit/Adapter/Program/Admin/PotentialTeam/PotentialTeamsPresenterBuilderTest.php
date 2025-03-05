<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\PotentialTeam;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeam;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PotentialTeamsPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildPotentialTeamsPresenter(): void
    {
        $team_1 = PotentialTeam::fromId(101, 'team_1', '');
        $team_2 = PotentialTeam::fromId(102, 'team_2', '');

        $teams_presenter = PotentialTeamsPresenterBuilder::buildPotentialTeamsPresenter([$team_1, $team_2]);

        self::assertCount(2, $teams_presenter);
        self::assertSame(101, $teams_presenter[0]->id);
        self::assertSame('team_1', $teams_presenter[0]->public_name);
        self::assertSame(102, $teams_presenter[1]->id);
        self::assertSame('team_2', $teams_presenter[1]->public_name);
    }
}
