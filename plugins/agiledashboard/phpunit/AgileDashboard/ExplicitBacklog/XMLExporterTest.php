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
use SimpleXMLElement;

final class XMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XMLExporter
     */
    private $exporter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao = Mockery::mock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);

        $this->exporter = new XMLExporter(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao
        );

        $this->project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('101')->getMock();
    }

    public function testItExportsExplicitBacklogUsageIfUsed(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard />
        ');

        $this->assertExplicitBacklogIsUsed();

        $this->exporter->exportExplicitBacklogConfiguration($this->project, $xml);

        $admin_node = $xml->admin;
        $this->assertNotNull($admin_node);
        $admin_scrum_node = $admin_node->scrum;
        $this->assertNotNull($admin_scrum_node);
        $admin_scrum_explicit_backlog_node = $admin_scrum_node->explicit_backlog;
        $this->assertNotNull($admin_scrum_explicit_backlog_node);
        $this->assertEquals('1', (string) $admin_scrum_explicit_backlog_node['is_used']);
    }

    public function testItDoesNotExportExplicitBacklogUsageIfNotUsed(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard />
        ');

        $this->assertExplicitBacklogIsNotUsed();

        $this->exporter->exportExplicitBacklogConfiguration($this->project, $xml);

        $this->assertEquals(0, $xml->count());
    }

    public function testItDoesNotExportExplicitBacklogContentIfNotUsed(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard />
        ');

        $this->assertExplicitBacklogIsNotUsed();

        $this->exporter->exportExplicitBacklogContent($this->project, $xml);

        $this->assertEquals(0, $xml->count());
    }

    public function testItDoesNotExportExplicitBacklogContentIfNotContent(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard />
        ');

        $this->assertExplicitBacklogIsUsed();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('getAllTopBacklogItemsForProjectSortedByRank')
            ->with(101)
            ->once()
            ->andReturn([]);

        $this->exporter->exportExplicitBacklogContent($this->project, $xml);

        $this->assertEquals(0, $xml->count());
    }

    public function testItExportsExplicitBacklogContent(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard />
        ');

        $this->assertExplicitBacklogIsUsed();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('getAllTopBacklogItemsForProjectSortedByRank')
            ->with(101)
            ->once()
            ->andReturn([
                ['artifact_id' => 148],
                ['artifact_id' => 158],
                ['artifact_id' => 152],
            ]);

        $this->exporter->exportExplicitBacklogContent($this->project, $xml);

        $top_backlog_node = $xml->top_backlog;
        $this->assertNotNull($top_backlog_node);

        $this->assertEquals('148', $top_backlog_node->artifact[0]['artifact_id']);
        $this->assertEquals('158', $top_backlog_node->artifact[1]['artifact_id']);
        $this->assertEquals('152', $top_backlog_node->artifact[2]['artifact_id']);
    }

    private function assertExplicitBacklogIsNotUsed(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnFalse();
    }

    private function assertExplicitBacklogIsUsed(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnTrue();
    }
}
