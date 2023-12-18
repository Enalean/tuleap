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

use ColinODell\PsrTestLogger\TestLogger;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class KanbanXmlImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \Tuleap\Kanban\KanbanColumnManager&MockObject
     */
    private $kanban_column_manager;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \Tuleap\Kanban\KanbanManager&MockObject
     */
    private $kanban_manager;
    /**
     * @var \Tuleap\Kanban\KanbanColumnFactory&MockObject
     */
    private $dashboard_kanban_column_factory;
    private KanbanXmlImporter $kanban_xml_importer;
    private \Tuleap\XML\MappingsRegistry $mappings_registry;
    /**
     * @var \Tuleap\Kanban\KanbanFactory&MockObject
     */
    private $kanban_factory;
    private TestLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dashboard_kanban_column_factory = $this->createMock(\Tuleap\Kanban\KanbanColumnFactory::class);
        $this->kanban_column_manager           = $this->createMock(\Tuleap\Kanban\KanbanColumnManager::class);
        $this->kanban_manager                  = $this->createMock(\Tuleap\Kanban\KanbanManager::class);
        $this->kanban_factory                  = $this->createMock(\Tuleap\Kanban\KanbanFactory::class);
        $this->mappings_registry               = new \Tuleap\XML\MappingsRegistry();

        $this->user                = new PFUser(['user_id' => 101, 'language_id' => 'en']);
        $this->logger              = new TestLogger();
        $this->kanban_xml_importer = new KanbanXmlImporter(
            $this->logger,
            $this->kanban_manager,
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

        $field_mapping = $this->createMock(\TrackerXmlFieldsMapping::class);

        $this->kanban_xml_importer->import(
            $xml,
            [],
            $field_mapping,
            $this->user,
            $this->mappings_registry
        );

        self::assertTrue($this->logger->hasInfo("0 Kanban found"));
    }

    public function testItImportsAKanbanWithItsOwnConfiguration(): void
    {
        $xml           = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <plannings />
                <kanban_list title="Kanban">
                    <kanban tracker_id="T22" name="My personal kanban" is_promoted="1">
                      <column wip="1" REF="V383"/>
                      <column wip="2" REF="V384"/>
                      <column wip="3" REF="V385"/>
                    </kanban>
                </kanban_list>
              </agiledashboard>
            </project>'
        );
        $field_mapping = $this->createMock(\TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);

        $this->kanban_manager->expects(self::once())->method('createKanban')->with('My personal kanban', 50, true)->willReturn(9);
        $this->kanban_column_manager->expects(self::exactly(3))->method('updateWipLimit');

        $this->kanban_factory->method('getKanbanForXmlImport')->willReturn($this->createMock(\Tuleap\Kanban\Kanban::class));
        $this->dashboard_kanban_column_factory->expects(self::exactly(3))
            ->method('getColumnForAKanban')
            ->willReturn($this->createMock(\Tuleap\Kanban\KanbanColumn::class));

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
            ],
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
        $field_mapping = $this->createMock(\TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);

        $this->kanban_manager->expects(self::once())->method('createKanban')->with('My personal kanban', false, 50)->willReturn(9);
        $this->kanban_column_manager->expects(self::exactly(3))->method('updateWipLimit');

        $this->kanban_factory->method('getKanbanForXmlImport')->willReturn($this->createMock(\Tuleap\Kanban\Kanban::class));
        $this->dashboard_kanban_column_factory->expects(self::exactly(3))
            ->method('getColumnForAKanban')
            ->willReturn($this->createMock(\Tuleap\Kanban\KanbanColumn::class));

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
            ],
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
        $field_mapping = $this->createMock(\TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);
        $this->kanban_factory->method('getKanbanForXmlImport')->willReturn($this->createMock(\Tuleap\Kanban\Kanban::class));

        $this->kanban_manager->expects(self::once())->method('createKanban')->with('My personal kanban', false, 50)->willReturn(9);

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
            ],
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
        $field_mapping = $this->createMock(\TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);

        $this->kanban_manager->expects(self::exactly(2))->method('createKanban')->willReturn(9, 10);
        $this->kanban_column_manager->expects(self::exactly(3))->method('updateWipLimit');

        $this->kanban_factory->method('getKanbanForXmlImport')->willReturn($this->createMock(\Tuleap\Kanban\Kanban::class));
        $this->dashboard_kanban_column_factory->expects(self::exactly(3))
            ->method('getColumnForAKanban')
            ->willReturn($this->createMock(\Tuleap\Kanban\KanbanColumn::class));

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
                'T21' => 51,
            ],
            $field_mapping,
            $this->user,
            $this->mappings_registry
        );
    }

    public function testItSetsKanbanIdInWidgetRegistry(): void
    {
        $xml           = new SimpleXMLElement(
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
        $field_mapping = $this->createMock(\TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);

        $this->kanban_factory
            ->method('getKanbanForXmlImport')
            ->willReturn(new \Tuleap\Kanban\Kanban(11221, TrackerTestBuilder::aTracker()->build(), false, ''));
        $this->dashboard_kanban_column_factory->expects(self::exactly(3))
            ->method('getColumnForAKanban')
            ->willReturn($this->createMock(\Tuleap\Kanban\KanbanColumn::class));

        $this->kanban_manager->expects(self::exactly(2))->method('createKanban')->willReturn(9, 10);
        $this->kanban_column_manager->expects(self::exactly(3))->method('updateWipLimit');

        $this->kanban_xml_importer->import(
            $xml,
            [
                'T22' => 50,
                'T21' => 51,
            ],
            $field_mapping,
            $this->user,
            $this->mappings_registry
        );
    }
}
