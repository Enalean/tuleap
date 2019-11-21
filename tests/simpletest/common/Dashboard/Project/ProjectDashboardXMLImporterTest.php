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

use Mockery\Exception;
use SimpleXMLElement;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\Widget\WidgetFactory;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\XML\MappingsRegistry;

class ProjectDashboardXMLImporter_Base extends \TuleapTestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var ProjectDashboardSaver
     */
    protected $project_dashboard_saver;
    /**
     * @var ProjectDashboardDao
     */
    protected $dao;
    /**
     * @var \Logger
     */
    protected $logger;
    /**
     * @var \Project
     */
    protected $project;

    /**
     * @var \PFUser
     */
    protected $user;

    /**
     * @var ProjectDashboardXMLImporter
     */
    protected $project_dashboard_importer;
    /**
     * @var \Tuleap\Dashboard\Widget\WidgetCreator
     */
    protected $widget_creator;
    /**
     * @var WidgetFactory
     */
    protected $widget_factory;
    /**
     * @var DashboardWidgetDao
     */
    protected $widget_dao;
    /**
     * @var MappingsRegistry
     */
    protected $mappings_registry;
    /**
     * @var \EventManager
     */
    protected $event_manager;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->dao                     = \Mockery::spy(\Tuleap\Dashboard\Project\ProjectDashboardDao::class);
        $this->project_dashboard_saver = new ProjectDashboardSaver(
            $this->dao
        );
        $this->widget_creator = \Mockery::spy(\Tuleap\Dashboard\Widget\WidgetCreator::class);
        $this->widget_factory = \Mockery::spy(\Tuleap\Widget\WidgetFactory::class);
        $this->widget_dao     = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $this->event_manager  = \Mockery::spy(\EventManager::class);

        $this->logger                     = \Mockery::spy(\Logger::class);
        $this->project_dashboard_importer = new ProjectDashboardXMLImporter(
            $this->project_dashboard_saver,
            $this->widget_factory,
            $this->widget_dao,
            $this->logger,
            $this->event_manager
        );

        $this->mappings_registry = new MappingsRegistry();

        $this->user    = \Mockery::spy(\PFUser::class);
        $this->project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
    }
}

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MultipleClasses
class ProjectDashboardXMLImporterTest extends ProjectDashboardXMLImporter_Base
{
    public function itLogsAWarningWhenUserDontHavePrivilegeToAddAProjectDashboard()
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
        $this->logger->shouldReceive('warn')->with('[Dashboards] '.$expected_exception->getMessage(), null)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itLogsAWarningWhenDashboardNameIsNull()
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
        $this->logger->shouldReceive('warn')->with('[Dashboards] '.$expected_exception->getMessage(), null)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itLogsAWarningWhenDashboardNameAlreadyExistsInTheSameProject()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar(array(1, 101, 'test')));

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="test" />
              </dashboards>
              </project>'
        );

        $expected_exception = new NameDashboardAlreadyExistsException();
        $this->logger->shouldReceive('warn')->with('[Dashboards] '.$expected_exception->getMessage(), null)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itImportsAProjectDashboard()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1" />
                <dashboard name="dashboard 2" />
              </dashboards>
              </project>'
        );

        $this->logger->shouldReceive('warn')->never();
        $this->dao->shouldReceive('save')->times(2);
        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }
}

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MultipleClasses
class ProjectDashboardXMLImporter_LinesTest extends ProjectDashboardXMLImporter_Base
{
    public function itImportsALine()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->dao->shouldReceive('save')->andReturns(10001);
        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('projectmembers');
        $widget->shouldReceive('getInstanceId')->andReturns(null);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns($widget);

        $this->widget_dao->shouldReceive('createLine')->with(10001, ProjectDashboardController::DASHBOARD_TYPE, 1)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itImportsAColumn()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->dao->shouldReceive('save')->andReturns(10001);

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->with(12, 1)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itSetOwnerAndIdExplicitlyToOvercomeWidgetDesignedToGatherThoseDataFromHTTP()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);

        $widget = \Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock();
        $widget->shouldReceive('setOwner')->with(101, ProjectDashboardController::LEGACY_DASHBOARD_TYPE)->once();

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns($widget);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itImportsAWidget()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itErrorsWhenWidgetNameIsUnknown()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="stuff"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->andReturns(null);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->logger->shouldReceive('error')->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itErrorsWhenWidgetContentCannotBeCreated()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="stuff"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->never();
        $this->widget_dao->shouldReceive('createColumn')->never();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getInstanceId')->andReturns(false)->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->logger->shouldReceive('error')->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itImportsTwoWidgetsInSameColumn()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->logger->shouldReceive('error')->never();

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectheartbeat')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 122, 2)->ordered();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itDoesntImportTwiceUniqueWidgets()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                  <line>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);
        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('projectheartbeat');
        $widget->shouldReceive('isUnique')->andReturns(true);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns($widget);

        $this->logger->shouldReceive('warn')->once();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(1);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 122, 1);

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itImportsUniqueWidgetsWhenThereAreInDifferentDashboards()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
                <dashboard name="dashboard 2">
                  <line>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createColumn')->andReturns(122)->ordered();
        $this->widget_dao->shouldReceive('createColumn')->andReturns(222)->ordered();
        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('projectheartbeat');
        $widget->shouldReceive('isUnique')->andReturns(true);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns($widget);

        $this->logger->shouldReceive('warn')->never();
        $this->logger->shouldReceive('error')->never();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 222, 1)->ordered();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itDoesntCreateLineAndColumnWhenWidgetIsNotValid()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="stuff"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->never();
        $this->widget_dao->shouldReceive('createColumn')->never();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(null);

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itDoesntImportAPersonalWidget()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="myprojects"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->never();
        $this->widget_dao->shouldReceive('createColumn')->never();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('myprojects')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('myprojects')->getMock());

        $this->logger->shouldReceive('error')->once();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itImportsTwoWidgetsInTwoColumns()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->logger->shouldReceive('error')->never();

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122)->ordered();
        $this->widget_dao->shouldReceive('createColumn')->andReturns(124)->ordered();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectheartbeat')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 124, 1)->ordered();

        $this->widget_dao->shouldReceive('adjustLayoutAccordinglyToNumberOfWidgets')->with(2, 12)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itImportsTwoWidgetsWithSetLayout()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line layout="two-columns-small-big">
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->dao->shouldReceive('save')->andReturns(144);

        $this->logger->shouldReceive('error')->never();

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122)->ordered();
        $this->widget_dao->shouldReceive('createColumn')->andReturns(124)->ordered();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectheartbeat')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 124, 1)->ordered();

        $this->widget_dao->shouldReceive('updateLayout')->with(12, 'two-columns-small-big')->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itFallsbackToAutomaticLayoutWhenLayoutIsUnknown()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line layout="foobar">
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->logger->shouldReceive('error')->never();
        $this->logger->shouldReceive('warn')->once();

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122)->ordered();
        $this->widget_dao->shouldReceive('createColumn')->andReturns(124)->ordered();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectheartbeat')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 124, 1)->ordered();

        $this->widget_dao->shouldReceive('updateLayout')->never();
        $this->widget_dao->shouldReceive('adjustLayoutAccordinglyToNumberOfWidgets')->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itImportsAWidgetWithPreferences()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectrss">
                        <preference name="rss">
                            <value name="title">Da feed</value>
                            <value name="url">https://stuff</value>
                        </preference>
                      </widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);

        $widget = \Mockery::mock(\Widget::class, [ 'getId' => 'projectrss' ])->shouldIgnoreMissing();
        $widget->shouldReceive('create')->with(\Mockery::on(function (\Codendi_Request $request) {
            if ($request->get('rss') &&
                $request->getInArray('rss', 'title') === 'Da feed' &&
                $request->getInArray('rss', 'url') === 'https://stuff') {
                return true;
            }
            return false;
        }))->once()->andReturns(35);

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectrss')->andReturns($widget);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectrss', 35, 122, 1)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function itSkipWidgetCreationWhenCreateRaisesExceptions()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectimageviewer">
                        <preference name="image">
                            <value name="title">Da kanban</value>
                            <value name="url">https://stuff</value>
                        </preference>
                      </widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->never();
        $this->widget_dao->shouldReceive('createColumn')->never();

        $this->mappings_registry->addReference('K123', 78998);

        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('projectimageviewer');
        $widget->shouldReceive('create')->andThrows(new \Exception("foo"));

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectimageviewer')->andReturns($widget);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }
}

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MultipleClasses
class ProjectDashboardXMLImporter_PluginTest extends ProjectDashboardXMLImporter_Base
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project_dashboard_importer = new ProjectDashboardXMLImporter(
            $this->project_dashboard_saver,
            $this->widget_factory,
            $this->widget_dao,
            $this->logger,
            $this->event_manager
        );
    }

    public function tearDown()
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function itImportsAWidgetDefinedInAPlugin()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns(\TestHelper::arrayToDar());

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="plugin_agiledashboard_projects_kanban">
                        <preference name="kanban">
                            <value name="title">Da kanban</value>
                            <reference name="id" REF="K123"/>
                        </preference>
                      </widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);

        $this->mappings_registry->addReference('K123', 78998);

        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('kanban');

        $this->event_manager->shouldReceive('processEvent')->with(\Mockery::on(function (ConfigureAtXMLImport $event) {
            $event->setContentId(35);
            $event->setWidgetIsConfigured();
            return true;
        }))->once();

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('plugin_agiledashboard_projects_kanban')->andReturns($widget);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('kanban', 35, 122, 1)->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }
}
