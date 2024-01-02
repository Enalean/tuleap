<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Dashboard\Project;

use SimpleXMLElement;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectDashboardXMLImporterTest extends ProjectDashboardXMLImporterBase
{
    public function testItLogsAWarningWhenUserDontHavePrivilegeToAddAProjectDashboard(): void
    {
        $user = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->build();

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="Project dashboard" />
              </dashboards>
              </project>'
        );

        $expected_exception = new UserCanNotUpdateProjectDashboardException();

        $this->project_dashboard_importer->import($xml, $user, $this->project, $this->mappings_registry);
        self::assertTrue($this->logger->hasWarning('[Dashboards] ' . $expected_exception->getMessage()));
    }

    public function testItLogsAWarningWhenDashboardNameIsNull(): void
    {
        $user = UserTestBuilder::aUser()
            ->withAdministratorOf($this->project)
            ->withoutSiteAdministrator()
            ->build();

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="" />
              </dashboards>
              </project>'
        );

        $expected_exception = new NameDashboardDoesNotExistException();

        $this->project_dashboard_importer->import($xml, $user, $this->project, $this->mappings_registry);
        self::assertTrue($this->logger->hasWarning('[Dashboards] ' . $expected_exception->getMessage()));
    }

    public function testItLogsAWarningWhenDashboardNameAlreadyExistsInTheSameProject(): void
    {
        $user = UserTestBuilder::aUser()
            ->withAdministratorOf($this->project)
            ->withoutSiteAdministrator()
            ->build();
        $this->dao->method('searchByProjectIdAndName')->willReturn([1, 101, 'test']);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="test" />
              </dashboards>
              </project>'
        );

        $expected_exception = new NameDashboardAlreadyExistsException();

        $this->project_dashboard_importer->import($xml, $user, $this->project, $this->mappings_registry);
        self::assertTrue($this->logger->hasWarning('[Dashboards] ' . $expected_exception->getMessage()));
    }

    public function testItImportsAProjectDashboard(): void
    {
        $user = UserTestBuilder::aUser()
            ->withAdministratorOf($this->project)
            ->withoutSiteAdministrator()
            ->build();
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1" />
                <dashboard name="dashboard 2" />
              </dashboards>
              </project>'
        );

        $this->dao->expects(self::exactly(2))->method('save');
        $this->project_dashboard_importer->import($xml, $user, $this->project, $this->mappings_registry);
        self::assertFalse($this->logger->hasWarningRecords());
    }
}
