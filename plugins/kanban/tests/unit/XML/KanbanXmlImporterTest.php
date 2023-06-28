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

namespace Tuleap\Kanban\XML;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use SimpleXMLElement;

class KanbanXmlImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Project
     */
    private $project;
    /**
     * @var \AgileDashboard_KanbanColumnManager
     */
    private $kanban_column_manager;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \AgileDashboard_KanbanManager
     */
    private $kanban_manager;
    /**
     * @var \AgileDashboard_KanbanColumnFactory
     */
    private $dashboard_kanban_column_factory;
    /**
     * @var KanbanXmlImporter
     */
    private $kanban_xml_importer;
    /**
     * @var \AgileDashboard_ConfigurationManager
     */
    private $agile_dashboard_configuration_manager;
    /**
     * @var \Tuleap\XML\MappingsRegistry
     */
    private $mappings_registry;
    /**
     * @var \AgileDashboard_KanbanFactory
     */
    private $kanban_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dashboard_kanban_column_factory       = \Mockery::spy(\AgileDashboard_KanbanColumnFactory::class);
        $this->agile_dashboard_configuration_manager = \Mockery::spy(\AgileDashboard_ConfigurationManager::class);
        $this->kanban_column_manager                 = \Mockery::spy(\AgileDashboard_KanbanColumnManager::class);
        $this->kanban_manager                        = \Mockery::spy(\AgileDashboard_KanbanManager::class);
        $this->kanban_factory                        = \Mockery::spy(\AgileDashboard_KanbanFactory::class);
        $this->mappings_registry                     = new \Tuleap\XML\MappingsRegistry();

        $this->user                = new PFUser(['user_id' => 101, 'language_id' => 'en']);
        $this->project             = \Mockery::spy(\Project::class, ['getID' => 101, 'getUserName' => false, 'isPublic' => false]);
        $this->kanban_xml_importer = new KanbanXmlImporter(
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            $this->kanban_manager,
            $this->agile_dashboard_configuration_manager,
            $this->kanban_column_manager,
            $this->kanban_factory,
            $this->dashboard_kanban_column_factory
        );
    }

    public function testItDontContinueImportWhenKanbanNodeIsNotFound(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <trackers>
                  <tracker xmlns="http://codendi.org/tracker" id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>t10</name>
                    <item_name>t11</item_name>
                    <description>t12</description>
                  </tracker>
                  <tracker xmlns="http://codendi.org/tracker" id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t20</name>
                    <item_name>t21</item_name>
                    <description>t22</description>
                  </tracker>
                  <tracker xmlns="http://codendi.org/tracker" id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>
              </trackers>
              <agiledashboard></agiledashboard>
              </project>'
        );

        $field_mapping = \Mockery::spy(\TrackerXmlFieldsMapping::class);

        $this->agile_dashboard_configuration_manager->shouldReceive('updateConfiguration')->never();
        $this->kanban_xml_importer->import(
            $xml,
            [],
            $this->project,
            $field_mapping,
            $this->user,
            $this->mappings_registry
        );
    }

    public function testItImportsAKanbanWithItsOwnConfiguration(): void
    {
        $xml           = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <plannings />
                <kanban_list title="Kanban">
                    <kanban tracker_id="T22" name="My personal kanban">
                      <column wip="1" REF="V383"/>
                      <column wip="2" REF="V384"/>
                      <column wip="3" REF="V385"/>
                    </kanban>
                </kanban_list>
              </agiledashboard>
            </project>'
        );
        $field_mapping = \Mockery::spy(\TrackerXmlFieldsMapping::class);

        $this->agile_dashboard_configuration_manager
            ->shouldReceive('updateConfiguration')
            ->with(
                101,
                0,
                1,
                Mockery::any(),
            )->once();
        $this->kanban_manager->shouldReceive('createKanban')->with('My personal kanban', 50)->once()->andReturn(9);
        $this->kanban_column_manager->shouldReceive('updateWipLimit')->times(3);

        $this->kanban_factory->shouldReceive('getKanbanForXmlImport')->andReturns(\Mockery::spy(\AgileDashboard_Kanban::class));
        $this->dashboard_kanban_column_factory->shouldReceive('getColumnForAKanban')
            ->times(3)
            ->andReturn(\Mockery::spy(\AgileDashboard_KanbanColumn::class));

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
            ],
            $this->project,
            $field_mapping,
            $this->user,
            $this->mappings_registry
        );
    }

    public function testItActivatesScrumAsWellOnlyIfThereAreImportedPlannings(): void
    {
        $xml           = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <plannings>
                    <planning name="Release Planning" />
                    <planning name="Sprint Planning" />
                </plannings>
                <kanban_list title="Kanban">
                    <kanban tracker_id="T22" name="My personal kanban">
                      <column wip="1" REF="V383"/>
                      <column wip="2" REF="V384"/>
                      <column wip="3" REF="V385"/>
                    </kanban>
                </kanban_list>
              </agiledashboard>
            </project>'
        );
        $field_mapping = \Mockery::spy(\TrackerXmlFieldsMapping::class);

        $this->agile_dashboard_configuration_manager
            ->shouldReceive('updateConfiguration')
            ->with(
                101,
                1,
                1,
                Mockery::any(),
            )->once();
        $this->kanban_manager->shouldReceive('createKanban')->with('My personal kanban', 50)->once()->andReturn(9);
        $this->kanban_column_manager->shouldReceive('updateWipLimit')->times(3);

        $this->kanban_factory->shouldReceive('getKanbanForXmlImport')->andReturns(\Mockery::spy(\AgileDashboard_Kanban::class));
        $this->dashboard_kanban_column_factory->shouldReceive('getColumnForAKanban')
            ->times(3)
            ->andReturn(\Mockery::spy(\AgileDashboard_KanbanColumn::class));

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
            ],
            $this->project,
            $field_mapping,
            $this->user,
            $this->mappings_registry
        );
    }

    public function testItImportsAKanbanWithASimpleConfiguration(): void
    {
        $xml           = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <plannings />
                <kanban_list title="Kanban">
                  <kanban tracker_id="T22" name="My personal kanban" />
                </kanban_list>
              </agiledashboard>
            </project>'
        );
        $field_mapping = \Mockery::spy(\TrackerXmlFieldsMapping::class);

        $this->agile_dashboard_configuration_manager
            ->shouldReceive('updateConfiguration')
            ->with(
                101,
                0,
                1,
                Mockery::any(),
            )->once();
        $this->kanban_manager->shouldReceive('createKanban')->with('My personal kanban', 50)->once()->andReturn(9);

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
            ],
            $this->project,
            $field_mapping,
            $this->user,
            $this->mappings_registry
        );
    }

    public function testItImportsMultipleKanban(): void
    {
        $xml           = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <plannings />
                <kanban_list title="Kanban">
                    <kanban tracker_id="T22" name="My personal kanban">
                      <column wip="1" REF="V383"/>
                      <column wip="2" REF="V384"/>
                      <column wip="3" REF="V385"/>
                    </kanban>
                 <kanban tracker_id="T21" name="Support request" />
                </kanban_list>
              </agiledashboard>
            </project>'
        );
        $field_mapping = \Mockery::spy(\TrackerXmlFieldsMapping::class);

        $this->agile_dashboard_configuration_manager
            ->shouldReceive('updateConfiguration')
            ->with(
                101,
                0,
                1,
                Mockery::any(),
            )->once();
        $this->kanban_manager->shouldReceive('createKanban')->times(2)->andReturn(9, 10);
        $this->kanban_column_manager->shouldReceive('updateWipLimit')->times(3);

        $this->kanban_factory->shouldReceive('getKanbanForXmlImport')->andReturns(\Mockery::spy(\AgileDashboard_Kanban::class));
        $this->dashboard_kanban_column_factory->shouldReceive('getColumnForAKanban')
            ->times(3)
            ->andReturn(\Mockery::spy(\AgileDashboard_KanbanColumn::class));

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
                'T21' => 51,
            ],
            $this->project,
            $field_mapping,
            $this->user,
            $this->mappings_registry
        );
    }

    public function testItSetsKanbanIdInWidgetRegistry(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <plannings />
                <kanban_list title="Kanban">
                    <kanban tracker_id="T22" name="My personal kanban" ID="K03">
                      <column wip="1" REF="V383"/>
                      <column wip="2" REF="V384"/>
                      <column wip="3" REF="V385"/>
                    </kanban>
                 <kanban tracker_id="T21" name="Support request" />
                </kanban_list>
              </agiledashboard>
            </project>'
        );

        $this->kanban_factory->shouldReceive('getKanbanForXmlImport')->andReturns(new \AgileDashboard_Kanban(11221, -1, ''));
        $this->dashboard_kanban_column_factory->shouldReceive('getColumnForAKanban')
            ->times(3)
            ->andReturn(\Mockery::spy(\AgileDashboard_KanbanColumn::class));

        $this->kanban_manager->shouldReceive('createKanban')->times(2)->andReturn(9, 10);

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
                'T21' => 51,
            ],
            $this->project,
            \Mockery::spy(\TrackerXmlFieldsMapping::class),
            $this->user,
            $this->mappings_registry
        );

        $this->assertEquals(11221, $this->mappings_registry->getReference('K03')->getId());
    }
}
