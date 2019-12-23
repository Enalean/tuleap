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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;

final class UnplannedCriterionOptionsProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var UnplannedCriterionOptionsProvider
     */
    private $provider;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao = Mockery::mock(ExplicitBacklogDao::class);
        $this->provider = new UnplannedCriterionOptionsProvider($this->explicit_backlog_dao);

        $this->project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(101)->getMock();
    }

    public function testItReturnsAnEmptyStringIfExplicitBacklogIsNotUsed(): void
    {
        $selected_option = '0';

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnFalse();

        $this->assertEmpty($this->provider->formatUnplannedAsSelectboxOption($this->project, $selected_option));
    }

    public function testItReturnsTheSelectboxOptionAsStringIfExplicitBacklogIsUsed(): void
    {
        $selected_option = '0';

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnTrue();

        $string_option = $this->provider->formatUnplannedAsSelectboxOption($this->project, $selected_option);

        $this->assertNotEmpty($string_option);
        $this->assertStringContainsString('option', $string_option);
        $this->assertStringNotContainsString('selected', $string_option);
    }

    public function testItReturnsTheSelectedSelectboxOptionAsStringIfExplicitBacklogIsUsedAndSelectedOptionIsUnplanned(): void
    {
        $selected_option = '-1';

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnTrue();

        $string_option = $this->provider->formatUnplannedAsSelectboxOption($this->project, $selected_option);

        $this->assertNotEmpty($string_option);
        $this->assertStringContainsString('option', $string_option);
        $this->assertStringContainsString('selected', $string_option);
    }
}
