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

declare(strict_types=1);

namespace Tuleap\Kanban\XML;

use ColinODell\PsrTestLogger\TestLogger;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_Report;
use TrackerXmlFieldsMapping;
use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanColumn;
use Tuleap\Kanban\KanbanColumnFactory;
use Tuleap\Kanban\KanbanColumnManager;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\KanbanManager;
use Tuleap\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\XML\MappingsRegistry;

#[DisableReturnValueGenerationForTestDoubles]
final class KanbanXmlImporterTest extends TestCase
{
    private KanbanColumnManager&MockObject $kanban_column_manager;
    private PFUser $user;
    private KanbanManager&MockObject $kanban_manager;
    private KanbanColumnFactory&MockObject $dashboard_kanban_column_factory;
    private KanbanXmlImporter $kanban_xml_importer;
    private MappingsRegistry $mappings_registry;
    private KanbanFactory&MockObject $kanban_factory;
    private TestLogger $logger;
    private TrackerReportUpdater&MockObject $tracker_report_updater;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dashboard_kanban_column_factory = $this->createMock(KanbanColumnFactory::class);
        $this->kanban_column_manager           = $this->createMock(KanbanColumnManager::class);
        $this->kanban_manager                  = $this->createMock(KanbanManager::class);
        $this->kanban_factory                  = $this->createMock(KanbanFactory::class);
        $this->tracker_report_updater          = $this->createMock(TrackerReportUpdater::class);
        $this->mappings_registry               = new MappingsRegistry();

        $this->user                = new PFUser(['user_id' => 101, 'language_id' => 'en']);
        $this->logger              = new TestLogger();
        $this->kanban_xml_importer = new KanbanXmlImporter(
            $this->logger,
            $this->kanban_manager,
            $this->kanban_column_manager,
            $this->kanban_factory,
            $this->dashboard_kanban_column_factory,
            $this->tracker_report_updater,
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

        $field_mapping = $this->createMock(TrackerXmlFieldsMapping::class);

        $this->kanban_xml_importer->import(
            $xml,
            [],
            $field_mapping,
            $this->user,
            $this->mappings_registry
        );

        self::assertTrue($this->logger->hasInfo('0 Kanban found'));
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
        $field_mapping = $this->createMock(TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);

        $this->kanban_manager->expects($this->once())->method('createKanban')->with('My personal kanban', 50, true)->willReturn(9);
        $this->kanban_column_manager->expects($this->exactly(3))->method('updateWipLimit');

        $this->kanban_factory->method('getKanbanForXmlImport')->willReturn($this->createMock(Kanban::class));
        $this->dashboard_kanban_column_factory->expects($this->exactly(3))
            ->method('getColumnForAKanban')
            ->willReturn($this->createMock(KanbanColumn::class));

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
        $field_mapping = $this->createMock(TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);

        $this->kanban_manager->expects($this->once())->method('createKanban')->with('My personal kanban', false, 50)->willReturn(9);
        $this->kanban_column_manager->expects($this->exactly(3))->method('updateWipLimit');

        $this->kanban_factory->method('getKanbanForXmlImport')->willReturn($this->createMock(Kanban::class));
        $this->dashboard_kanban_column_factory->expects($this->exactly(3))
            ->method('getColumnForAKanban')
            ->willReturn($this->createMock(KanbanColumn::class));

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
        $field_mapping = $this->createMock(TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);
        $this->kanban_factory->method('getKanbanForXmlImport')->willReturn($this->createMock(Kanban::class));

        $this->kanban_manager->expects($this->once())->method('createKanban')->with('My personal kanban', false, 50)->willReturn(9);

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
        $field_mapping = $this->createMock(TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);

        $this->kanban_manager->expects($this->exactly(2))->method('createKanban')->willReturn(9, 10);
        $this->kanban_column_manager->expects($this->exactly(3))->method('updateWipLimit');

        $this->kanban_factory->method('getKanbanForXmlImport')->willReturn($this->createMock(Kanban::class));
        $this->dashboard_kanban_column_factory->expects($this->exactly(3))
            ->method('getColumnForAKanban')
            ->willReturn($this->createMock(KanbanColumn::class));

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
        $field_mapping = $this->createMock(TrackerXmlFieldsMapping::class);
        $field_mapping->method('getNewOpenValueId')->willReturn(123);

        $this->kanban_factory
            ->method('getKanbanForXmlImport')
            ->willReturn(new Kanban(11221, TrackerTestBuilder::aTracker()->build(), false, ''));
        $this->dashboard_kanban_column_factory->expects($this->exactly(3))
            ->method('getColumnForAKanban')
            ->willReturn($this->createMock(KanbanColumn::class));

        $this->kanban_manager->expects($this->exactly(2))->method('createKanban')->willReturn(9, 10);
        $this->kanban_column_manager->expects($this->exactly(3))->method('updateWipLimit');

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

    public function testItImportKanbanWithItsTrackerReports(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <plannings />
                <kanban_list title="Kanban">
                    <kanban tracker_id="T22" name="My personal kanban" ID="K03">
                        <tracker-reports>
                            <tracker-report id="REPORT_588"/>
                            <tracker-report id="REPORT_654"/>
                        </tracker-reports>
                    </kanban>
                </kanban_list>
              </agiledashboard>
            </project>'
        );

        $kanban = new Kanban(11, TrackerTestBuilder::aTracker()->build(), false, '');

        $this->kanban_manager->expects($this->once())->method('createKanban')->willReturn(11);
        $this->kanban_factory->method('getKanbanForXmlImport')->willReturn($kanban);

        $this->mappings_registry->addReference('REPORT_588', new Tracker_Report(588, 'name', 'Public rapport', 0, 0, null, false, 2, 1, false, '', null, 0));
        $this->mappings_registry->addReference('REPORT_654', new Tracker_Report(654, 'name', 'Public rapport', 0, 0, null, false, 2, 1, false, '', null, 0));

        $this->tracker_report_updater->expects($this->once())->method('save')->with($kanban, [588, 654]);

        $this->kanban_xml_importer->import(
            $xml,
            ['T22' => 50],
            $this->createStub(TrackerXmlFieldsMapping::class),
            $this->user,
            $this->mappings_registry
        );
    }
}
