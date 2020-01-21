<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class ArtifactLinksUsageDuplicatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Project
     */
    private $template;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactLinksUsageDao
     */
    private $dao;
    /**
     * @var ArtifactLinksUsageDuplicator
     */
    private $duplicator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao        = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);
        $this->duplicator = new ArtifactLinksUsageDuplicator($this->dao);

        $this->template = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
        $this->project  = \Mockery::spy(\Project::class, ['getID' => 102, 'getUnixName' => false, 'isPublic' => false]);
    }

    public function testItActivatesTheArtifactLinkTypesIfTemplateAlreadyUseThem(): void
    {
        $this->dao->shouldReceive('isProjectUsingArtifactLinkTypes')->with(101)->andReturns(true);
        $this->dao->shouldReceive('duplicate')->with(101, 102)->once();

        $this->duplicator->duplicate($this->template, $this->project);
    }

    public function testItActivatesTheArtifactLinkTypesIfTemplateDoesNotUseTrackerServiceAndNewProjectUseIt(): void
    {
        $this->dao->shouldReceive('isProjectUsingArtifactLinkTypes')->with(101)->andReturns(false);
        $this->template->shouldReceive('usesService')->with('plugin_tracker')->andReturns(false);
        $this->project->shouldReceive('usesService')->with('plugin_tracker')->andReturns(true);

        $this->dao->shouldReceive('duplicate')->with(101, 102)->once();

        $this->duplicator->duplicate($this->template, $this->project);
    }

    public function testItDoesNotActivateTheArtifactLinkTypesIfTemplateDoesNotUseIt(): void
    {
        $this->dao->shouldReceive('isProjectUsingArtifactLinkTypes')->with(101)->andReturns(false);
        $this->template->shouldReceive('usesService')->with('plugin_tracker')->andReturns(true);
        $this->project->shouldReceive('usesService')->with('plugin_tracker')->andReturns(true);

        $this->dao->shouldReceive('duplicate')->with(101, 102)->never();

        $this->duplicator->duplicate($this->template, $this->project);
    }

    public function testItDoesNotActivateTheArtifactLinkTypesIfTemplateAndNewProjectDoesNotUseTheService(): void
    {
        $this->dao->shouldReceive('isProjectUsingArtifactLinkTypes')->with(101)->andReturns(false);
        $this->template->shouldReceive('usesService')->with('plugin_tracker')->andReturns(false);
        $this->project->shouldReceive('usesService')->with('plugin_tracker')->andReturns(false);

        $this->dao->shouldReceive('duplicate')->with(101, 102)->never();

        $this->duplicator->duplicate($this->template, $this->project);
    }
}
