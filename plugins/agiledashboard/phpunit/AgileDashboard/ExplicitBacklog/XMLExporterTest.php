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

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao = Mockery::mock(ExplicitBacklogDao::class);

        $this->exporter = new XMLExporter($this->explicit_backlog_dao);

        $this->project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('101')->getMock();
    }

    public function testItExportsExplicitBacklogUsageIfUsed(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard />
        ');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnTrue();

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

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnFalse();

        $this->exporter->exportExplicitBacklogConfiguration($this->project, $xml);

        $this->assertEquals(0, $xml->count());
    }
}
