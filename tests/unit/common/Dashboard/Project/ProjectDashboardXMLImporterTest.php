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

require_once __DIR__ . '/ProjectDashboardXMLImporterBase.php';

use Psr\Log\LogLevel;
use SimpleXMLElement;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;

class ProjectDashboardXMLImporterTest extends ProjectDashboardXMLImporterBase
{
    public function testItLogsAWarningWhenUserDontHavePrivilegeToAddAProjectDashboard()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(false);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="Project dashboard" />
              </dashboards>
              </project>'
        );

        $expected_exception = new UserCanNotUpdateProjectDashboardException();
        $this->logger->shouldReceive('log')->with(LogLevel::WARNING, '[Dashboards] ' . $expected_exception->getMessage(), [])->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItLogsAWarningWhenDashboardNameIsNull()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="" />
              </dashboards>
              </project>'
        );

        $expected_exception = new NameDashboardDoesNotExistException();
        $this->logger->shouldReceive('log')->with(LogLevel::WARNING, '[Dashboards] ' . $expected_exception->getMessage(), [])->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItLogsAWarningWhenDashboardNameAlreadyExistsInTheSameProject()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([1, 101, 'test']);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="test" />
              </dashboards>
              </project>'
        );

        $expected_exception = new NameDashboardAlreadyExistsException();
        $this->logger->shouldReceive('log')->with(LogLevel::WARNING, '[Dashboards] ' . $expected_exception->getMessage(), [])->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsAProjectDashboard()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1" />
                <dashboard name="dashboard 2" />
              </dashboards>
              </project>'
        );

        $this->logger->shouldReceive('log')->with(LogLevel::WARNING, \Mockery::any(), \Mockery::any())->never();
        $this->dao->shouldReceive('save')->times(2);
        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }
}
