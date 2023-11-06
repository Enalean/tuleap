<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\ServiceAdministration;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class RedirectURITest extends TestCase
{
    private const PROJECT_ID = 163;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
    }

    public function testItBuildsScrumAdministrationURI(): void
    {
        self::assertSame(
            '/plugins/agiledashboard/?group_id=' . self::PROJECT_ID . '&action=admin&pane=scrum',
            (string) RedirectURI::buildScrumAdministration($this->project)
        );
    }

    public function testItBuildsProjectBacklogURI(): void
    {
        self::assertSame(
            '/plugins/agiledashboard/?group_id=' . self::PROJECT_ID . '&action=show-top&pane=topplanning-v2',
            (string) RedirectURI::buildProjectBacklog($this->project)
        );
    }
}
