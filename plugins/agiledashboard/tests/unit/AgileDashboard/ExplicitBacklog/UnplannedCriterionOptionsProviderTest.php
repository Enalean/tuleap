<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class UnplannedCriterionOptionsProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UnplannedCriterionOptionsProvider $provider;
    private ExplicitBacklogDao|\PHPUnit\Framework\MockObject\MockObject $explicit_backlog_dao;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao = $this->createMock(ExplicitBacklogDao::class);
        $this->provider             = new UnplannedCriterionOptionsProvider($this->explicit_backlog_dao);

        $this->project = ProjectTestBuilder::aProject()->build();
    }

    public function testItReturnsAnEmptyStringIfExplicitBacklogIsNotUsed(): void
    {
        $selected_option = '0';

        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->assertEmpty($this->provider->formatUnplannedAsSelectboxOption($this->project, $selected_option));
    }

    public function testItReturnsTheSelectboxOptionAsStringIfExplicitBacklogIsUsed(): void
    {
        $selected_option = '0';

        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $string_option = $this->provider->formatUnplannedAsSelectboxOption($this->project, $selected_option);

        $this->assertNotEmpty($string_option);
        $this->assertStringContainsString('option', $string_option);
        $this->assertStringNotContainsString('selected', $string_option);
    }

    public function testItReturnsTheSelectedSelectboxOptionAsStringIfExplicitBacklogIsUsedAndSelectedOptionIsUnplanned(): void
    {
        $selected_option = '-1';

        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $string_option = $this->provider->formatUnplannedAsSelectboxOption($this->project, $selected_option);

        $this->assertNotEmpty($string_option);
        $this->assertStringContainsString('option', $string_option);
        $this->assertStringContainsString('selected', $string_option);
    }
}
