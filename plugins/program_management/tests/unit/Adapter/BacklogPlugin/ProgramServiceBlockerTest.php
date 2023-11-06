<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\BacklogPlugin;

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ProgramServiceBlockerTest extends TestCase
{
    private const PROJECT_ID = 101;

    /**
     * @return Option<string>
     */
    private function getBlockedMessage(\Project $project): Option
    {
        $verifier = new ProgramServiceBlocker(
            RetrieveFullProjectStub::withProject($project)
        );

        return $verifier->shouldProgramServiceBeBlocked(
            UserIdentifierStub::buildGenericUser(),
            ProjectIdentifierStub::buildWithId((int) $project->getID())
        );
    }

    public function testItDoesNotBlockProgramService(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withoutServices()
            ->build();

        $blocked_message = $this->getBlockedMessage($project);
        self::assertFalse($blocked_message->isValue());
    }

    public function testItBlocksProgramServiceWhenBacklogServiceIsUsed(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService(\AgileDashboardPlugin::PLUGIN_SHORTNAME)
            ->build();

        $option = $this->getBlockedMessage($project);
        self::assertTrue($option->isValue());
    }
}
