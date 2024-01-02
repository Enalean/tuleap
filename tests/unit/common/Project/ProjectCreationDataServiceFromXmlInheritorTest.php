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

use PHPUnit\Framework\MockObject\MockObject;
use Service;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class ProjectCreationDataServiceFromXmlInheritorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProjectCreationDataServiceFromXmlInheritor $service_inheritor;
    private \ServiceManager&MockObject $service_manager;

    protected function setUp(): void
    {
        $this->service_manager = $this->createMock(\ServiceManager::class);

        $this->service_inheritor = new ProjectCreationDataServiceFromXmlInheritor($this->service_manager);

        $admin_service = $this->createMock(Service::class);
        $admin_service->method('getShortName')->willReturn('admin');
        $admin_service->method('getId')->willReturn(1);
        $git_service = $this->createMock(Service::class);
        $git_service->method('getShortName')->willReturn('plugin_git');
        $git_service->method('getId')->willReturn(10);

        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturn([$admin_service, $git_service,]);
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

        $project = ProjectTestBuilder::aProject()->build();

        $result = $this->service_inheritor->markUsedServicesFromXML($xml, $project);

        $expected_result = [
            1  => ['is_used' => true],
            10 => ['is_used' => true],
        ];

        self::assertEquals($expected_result, $result);
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

        $project = ProjectTestBuilder::aProject()->build();

        $result = $this->service_inheritor->markUsedServicesFromXML($xml, $project);

        $expected_result = [
            1  => ['is_used' => true],
            10 => ['is_used' => true],
        ];

        self::assertEquals($expected_result, $result);
    }
}
