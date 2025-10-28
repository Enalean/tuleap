<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Git\Administration;

use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AdministrationPaneBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withUnixName('test')->build();
    }

    public function testItBuildsAPane(): void
    {
        $pane = AdministrationPaneBuilder::buildPane($this->project);

        self::assertSame('Jenkins', $pane->pane_name);
        self::assertStringContainsString(
            '/test/administration/jenkins',
            $pane->uri
        );
        self::assertFalse($pane->is_active);
    }

    public function testItBuildsAnActivePane(): void
    {
        $pane = AdministrationPaneBuilder::buildActivePane($this->project);

        self::assertSame('Jenkins', $pane->pane_name);
        self::assertStringContainsString(
            '/test/administration/jenkins',
            $pane->uri
        );
        self::assertTrue($pane->is_active);
    }
}
