<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Project;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Service;

final class ProjectCreationDataServiceFromXmlInheritorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectCreationDataServiceFromXmlInheritor
     */
    private $service_inheritor;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ServiceManager
     */
    private $service_manager;

    protected function setUp(): void
    {
        $this->service_manager = \Mockery::mock(\ServiceManager::class);

        $this->service_inheritor = new ProjectCreationDataServiceFromXmlInheritor($this->service_manager);

        $admin_service = \Mockery::mock(Service::class);
        $admin_service->shouldReceive('getShortName')->andReturn('admin');
        $admin_service->shouldReceive('getId')->andReturn(1);
        $git_service = \Mockery::mock(Service::class);
        $git_service->shouldReceive('getShortName')->andReturn('plugin_git');
        $git_service->shouldReceive('getId')->andReturn(10);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->andReturn(
            [
                $admin_service,
                $git_service
            ]
        );
    }

    public function testItBuildsServiceUsageFromXml(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                  <project>
                      <services>
                        <service shortname="admin" enabled="1"/>
                        <service shortname="plugin_git" enabled="1"/>
                      </services>
                  </project>'
        );

        $project = \Mockery::mock(\Project::class);

        $result = $this->service_inheritor->markUsedServicesFromXML($xml, $project);

        $expected_result = [
            1 => ['is_used' => true],
            10 => ['is_used' => true],
        ];

        $this->assertEquals($expected_result, $result);
    }

    public function testItAddsAdminServiceIfItIsNotPresentInXml(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                  <project>
                      <services>
                        <service shortname="plugin_git" enabled="1"/>
                      </services>
                  </project>'
        );

        $project = \Mockery::mock(\Project::class);

        $result = $this->service_inheritor->markUsedServicesFromXML($xml, $project);

        $expected_result = [
            1 => ['is_used' => true],
            10 => ['is_used' => true],
        ];

        $this->assertEquals($expected_result, $result);
    }
}
