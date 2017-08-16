<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
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

class ProjectDashboardXMLImporterTest extends \TuleapTestCase
{
    /**
     * @var ProjectDashboardSaver
     */
    private $project_dashboard_saver;
    /**
     * @var ProjectDashboardDao
     */
    private $dao;
    /**
     * @var \Logger
     */
    private $logger;
    /**
     * @var \Project
     */
    private $project;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var ProjectDashboardXMLImporter
     */
    private $project_dashboard_importer;

    public function setUp()
    {
        parent::setUp();

        $this->dao                     = mock('Tuleap\Dashboard\Project\ProjectDashboardDao');
        $this->project_dashboard_saver = new ProjectDashboardSaver(
            $this->dao
        );

        $this->logger                     = mock('Logger');
        $this->project_dashboard_importer = new ProjectDashboardXMLImporter(
            $this->project_dashboard_saver,
            $this->logger
        );

        $this->user    = mock('PFUser');
        $this->project = aMockProject()->withId(101)->build();
    }


    public function itLogsAWarningWhenUserDontHavePrivilegeToAddAProjectDashboard()
    {
        stub($this->user)->isAdmin(101)->returns(false);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="Project dashboard" />
              </dashboards>
              </project>'
        );

        $expected_exception = new UserCanNotUpdateProjectDashboardException();
        stub($this->logger)->warn($expected_exception->getMessage())->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project);
    }

    public function itLogsAWarningWhenDashboardNameIsNull()
    {
        stub($this->user)->isAdmin(101)->returns(true);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="" />
              </dashboards>
              </project>'
        );

        $expected_exception = new NameDashboardDoesNotExistException();
        stub($this->logger)->warn($expected_exception->getMessage())->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project);
    }

    public function itLogsAWarningWhenDashboardNameAlreadyExistsInTheSameProject()
    {
        stub($this->user)->isAdmin(101)->returns(true);
        stub($this->dao)->searchByProjectIdAndName()->returnsDar(
            array(1, 101, 'test')
        );

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="test" />
              </dashboards>
              </project>'
        );

        $expected_exception = new NameDashboardAlreadyExistsException();
        stub($this->logger)->warn($expected_exception->getMessage())->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project);
    }


    public function itImportsAProjectDashboard()
    {
        stub($this->user)->isAdmin(101)->returns(true);
        stub($this->dao)->searchByProjectIdAndName()->returnsDar();

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1" />
                <dashboard name="dashboard 2" />
              </dashboards>
              </project>'
        );

        stub($this->logger)->warn()->never();
        stub($this->project_dashboard_saver)->expectCallCount('save', 3);
        $this->project_dashboard_importer->import($xml, $this->user, $this->project);
    }
}
