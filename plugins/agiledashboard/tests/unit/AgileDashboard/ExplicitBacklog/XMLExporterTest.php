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
use SimpleXMLElement;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class XMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private XMLExporter $exporter;
    private Project $project;
    private ExplicitBacklogDao|\PHPUnit\Framework\MockObject\MockObject $explicit_backlog_dao;
    private ArtifactsInExplicitBacklogDao|\PHPUnit\Framework\MockObject\MockObject $artifacts_in_explicit_backlog_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao              = $this->createMock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);

        $this->exporter = new XMLExporter(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao
        );

        $this->project = ProjectTestBuilder::aProject()->build();
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

        $this->artifacts_in_explicit_backlog_dao
            ->expects(self::once())
            ->method('getAllTopBacklogItemsForProjectSortedByRank')
            ->with(101)
            ->willReturn([]);

        $this->exporter->exportExplicitBacklogContent($this->project, $xml);

        $this->assertEquals(0, $xml->count());
    }

    public function testItExportsExplicitBacklogContent(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard />
        ');

        $this->assertExplicitBacklogIsUsed();

        $this->artifacts_in_explicit_backlog_dao
            ->expects(self::once())
            ->method('getAllTopBacklogItemsForProjectSortedByRank')
            ->with(101)
            ->willReturn([
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
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);
    }

    private function assertExplicitBacklogIsUsed(): void
    {
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);
    }
}
