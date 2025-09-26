<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall;

use Cardwall_OnTop_ColumnDao;
use Cardwall_OnTop_ColumnMappingFieldDao;
use Cardwall_OnTop_ColumnMappingFieldValueDao;
use Cardwall_OnTop_Dao;
use CardwallConfigXmlImport;
use CardwallFromXmlImportCannotBeEnabledException;
use Event;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\XML\ParseExceptionWithErrors;
use XML_ParseException;
use XML_RNGValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardwallConfigXmlImportTest extends TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private SimpleXMLElement $default_xml_input;
    private SimpleXMLElement $enhanced_xml_input;
    private EventManager&MockObject $event_manager;
    private Cardwall_OnTop_Dao&MockObject $cardwall_ontop_dao;
    private CardwallConfigXmlImport $cardwall_config_xml_import;
    private array $mapping;
    private array $field_mapping;
    private Cardwall_OnTop_ColumnDao&MockObject $column_dao;
    private Cardwall_OnTop_ColumnMappingFieldDao&MockObject $mapping_field_dao;
    private Cardwall_OnTop_ColumnMappingFieldValueDao&MockObject $mapping_field_value_dao;
    private int $group_id;
    private XML_RNGValidator&MockObject $xml_validator;
    private LoggerInterface $logger;
    private Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping;

    #[\Override]
    protected function setUp(): void
    {
        $this->default_xml_input = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <empty_section />
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
              <cardwall>
                <trackers>
                    <tracker id="T101"/>
                    <tracker id="T102">
                        <columns>
                            <column label="Todo"/>
                            <column label="On going"/>
                            <column label="Review"/>
                            <column label="Done"/>
                        </columns>
                    </tracker>
                </trackers>
              </cardwall>
              <agiledashboard/>
            </project>');

        $this->enhanced_xml_input = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <empty_section />
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
                        <formElements>
                            <formElement type="sb" ID="F1" rank="0">
                                <name>progress</name>
                                <label><![CDATA[Progress]]></label>
                                <bind type="static" is_rank_alpha="0">
                                  <items>
                                    <item ID="V1" label="To do" is_hidden="0"/>
                                    <item ID="V2" label="On going" is_hidden="0"/>
                                    <item ID="V3" label="Review" is_hidden="0"/>
                                    <item ID="V4" label="Done" is_hidden="0"/>
                                  </items>
                                  <default_values>
                                    <value REF="V320"/>
                                  </default_values>
                                </bind>
                            </formElement>
                        </formElements>
                  </tracker>
              </trackers>
              <cardwall>
                <trackers>
                    <tracker id="T101"/>
                    <tracker id="T102">
                        <columns>
                            <column id="C1" label="Todo"/>
                            <column id="C2" label="On going" bg_red="255" bg_green="255" bg_blue="240"/>
                            <column id="C3" label="Review"/>
                            <column id="C4" label="Done" tlp_color_name="fiesta-red"/>
                        </columns>
                        <mappings>
                            <mapping tracker_id="T103" field_id="F1">
                                <values>
                                    <value value_id="V1" column_id="C1"/>
                                    <value value_id="V4" column_id="C3"/>
                                    <value value_id="" column_id="C4"/>
                                </values>
                            </mapping>
                        </mappings>
                    </tracker>
                </trackers>
              </cardwall>
              <agiledashboard/>
            </project>');

        $field    = SelectboxFieldBuilder::aSelectboxField(1)->build();
        $value_01 = ChangesetValueListTestBuilder::aListOfValue(401, ChangesetTestBuilder::aChangeset(1)->build(), $field)->build();
        $value_02 = ChangesetValueListTestBuilder::aListOfValue(402, ChangesetTestBuilder::aChangeset(2)->build(), $field)->build();
        $value_03 = ChangesetValueListTestBuilder::aListOfValue(403, ChangesetTestBuilder::aChangeset(3)->build(), $field)->build();
        $value_04 = ChangesetValueListTestBuilder::aListOfValue(404, ChangesetTestBuilder::aChangeset(4)->build(), $field)->build();

        $this->mapping = [
            'T101' => 444,
            'T102' => 555,
            'T103' => 666,
        ];

        $this->field_mapping = [
            'F1' => $field,
            'V1' => $value_01,
            'V2' => $value_02,
            'V3' => $value_03,
            'V4' => $value_04,
        ];

        $this->cardwall_ontop_dao      = $this->createMock(Cardwall_OnTop_Dao::class);
        $this->column_dao              = $this->createMock(Cardwall_OnTop_ColumnDao::class);
        $this->mapping_field_dao       = $this->createMock(Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->mapping_field_value_dao = $this->createMock(Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $this->group_id                = 145;
        $this->event_manager           = $this->createMock(EventManager::class);
        $this->xml_validator           = $this->createMock(XML_RNGValidator::class);
        $this->logger                  = new NullLogger();
        $this->artifact_id_mapping     = new Tracker_XML_Importer_ArtifactImportedMapping();

        $this->cardwall_ontop_dao->method('startTransaction');
        $this->cardwall_ontop_dao->method('commit');
        $this->xml_validator->method('validate');

        $this->cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $this->artifact_id_mapping,
            $this->cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $this->xml_validator,
            $this->logger
        );
    }

    public function testItStoresAllTheCardwallOnTop(): void
    {
        $this->event_manager->method('processEvent');

        $this->cardwall_ontop_dao->expects($this->exactly(2))->method('enable')->willReturn(true);
        $this->cardwall_ontop_dao->expects($this->exactly(2))->method('enableFreestyleColumns');
        $this->column_dao->method('createWithcolor');

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItCreatesTheFreestyleColumns(): void
    {
        $this->event_manager->method('processEvent');

        $this->cardwall_ontop_dao->method('enable')->willReturn(true);
        $this->cardwall_ontop_dao->method('enableFreestyleColumns');
        $matcher = self::exactly(4);

        $this->column_dao->expects($matcher)->method('createWithcolor')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(555, $parameters[0]);
                self::assertSame('Todo', $parameters[1]);
                self::assertSame('', $parameters[2]);
                self::assertSame('', $parameters[3]);
                self::assertSame('', $parameters[4]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(555, $parameters[0]);
                self::assertSame('On going', $parameters[1]);
                self::assertSame('', $parameters[2]);
                self::assertSame('', $parameters[3]);
                self::assertSame('', $parameters[4]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame(555, $parameters[0]);
                self::assertSame('Review', $parameters[1]);
                self::assertSame('', $parameters[2]);
                self::assertSame('', $parameters[3]);
                self::assertSame('', $parameters[4]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                self::assertSame(555, $parameters[0]);
                self::assertSame('Done', $parameters[1]);
                self::assertSame('', $parameters[2]);
                self::assertSame('', $parameters[3]);
                self::assertSame('', $parameters[4]);
            }
        });

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItCreatesTheFreestyleColumnsWithColor(): void
    {
        $this->event_manager->method('processEvent');

        $this->cardwall_ontop_dao->method('enable')->willReturn(true);
        $this->cardwall_ontop_dao->method('enableFreestyleColumns');
        $this->mapping_field_dao->method('create');
        $this->mapping_field_value_dao->method('save');
        $matcher = $this->exactly(3);

        $this->column_dao->expects($matcher)->method('createWithcolor')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(555, $parameters[0]);
                self::assertSame('Todo', $parameters[1]);
                self::assertSame('', $parameters[2]);
                self::assertSame('', $parameters[3]);
                self::assertSame('', $parameters[4]);
                return 20;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(555, $parameters[0]);
                self::assertSame('On going', $parameters[1]);
                self::assertSame(255, $parameters[2]);
                self::assertSame(255, $parameters[3]);
                self::assertSame(240, $parameters[4]);
                return 21;
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame(555, $parameters[0]);
                self::assertSame('Review', $parameters[1]);
                self::assertSame('', $parameters[2]);
                self::assertSame('', $parameters[3]);
                self::assertSame('', $parameters[4]);
                return 22;
            }
        });
        $this->column_dao->expects($this->once())->method('createWithTLPColor')->with(555, 'Done', 'fiesta-red');

        $this->cardwall_config_xml_import->import($this->enhanced_xml_input);
    }

    public function testItDoesNotCreateMappingAndMappingValueinDefaultXML(): void
    {
        $this->event_manager->method('processEvent');

        $this->cardwall_ontop_dao->method('enable')->willReturn(true);
        $this->cardwall_ontop_dao->method('enableFreestyleColumns');
        $this->column_dao->method('createWithcolor');

        $this->mapping_field_dao->expects($this->never())->method('create');
        $this->mapping_field_value_dao->expects($this->never())->method('save');

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItCreatesMappingAndMappingValue(): void
    {
        $this->event_manager->method('processEvent');

        $this->cardwall_ontop_dao->method('enable')->willReturn(true);
        $this->cardwall_ontop_dao->method('enableFreestyleColumns');

        $this->column_dao->method('createWithcolor')
            ->willReturnOnConsecutiveCalls(20, 21, 22);
        $this->column_dao->method('createWithTLPColor')->willReturn(23);

        $this->mapping_field_dao->expects($this->once())->method('create')->with(555, 666, 1);
        $matcher = self::exactly(3);

        $this->mapping_field_value_dao->expects($matcher)->method('save')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(555, $parameters[0]);
                self::assertSame(666, $parameters[1]);
                self::assertSame(1, $parameters[2]);
                self::assertSame(401, $parameters[3]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(555, $parameters[0]);
                self::assertSame(666, $parameters[1]);
                self::assertSame(1, $parameters[2]);
                self::assertSame(404, $parameters[3]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame(555, $parameters[0]);
                self::assertSame(666, $parameters[1]);
                self::assertSame(1, $parameters[2]);
                self::assertSame(100, $parameters[3]);
            }
        });

        $this->cardwall_config_xml_import->import($this->enhanced_xml_input);
    }

    public function testItProcessesANewEventIfAllCardwallAreEnabled(): void
    {
        $this->event_manager->method('processEvent')->with(
            Event::IMPORT_XML_PROJECT_CARDWALL_DONE,
            [
                'project_id'          => $this->group_id,
                'xml_content'         => $this->default_xml_input,
                'mapping'             => $this->mapping,
                'logger'              => $this->logger,
                'artifact_id_mapping' => $this->artifact_id_mapping,
            ]
        );

        $this->cardwall_ontop_dao->expects($this->exactly(2))->method('enable')->willReturn(true);
        $this->cardwall_ontop_dao->method('enableFreestyleColumns');
        $this->column_dao->method('createWithcolor');

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItDoesNotProcessAnEventIfAtLeastOneCardwallCannotBeEnabledAndThrowsAnException(): void
    {
        $cardwall_ontop_dao = $this->createMock(Cardwall_OnTop_Dao::class);
        $cardwall_ontop_dao->expects($this->once())->method('enable')->willReturn(false);
        $cardwall_ontop_dao->method('startTransaction');
        $cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $this->artifact_id_mapping,
            $cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $this->xml_validator,
            $this->logger
        );

        $this->event_manager->expects($this->never())->method('processEvent')->with(Event::IMPORT_XML_PROJECT_CARDWALL_DONE, self::anything());

        $this->expectException(CardwallFromXmlImportCannotBeEnabledException::class);
        $cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItThrowsAnExceptionIfXmlDoesNotMatchRNG(): void
    {
        $xml_validator = $this->createMock(XML_RNGValidator::class);
        $xml_validator->method('validate')->willThrowException(new ParseExceptionWithErrors('', [], []));

        $cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $this->artifact_id_mapping,
            $this->cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $xml_validator,
            $this->logger
        );

        $this->expectException(XML_ParseException::class);

        $cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testEventIsSentToCommunicateTheCardwallImportIsDoneEvenWhenThereIsNoCardwallNode(): void
    {
        $cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $this->artifact_id_mapping,
            $this->cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $this->createMock(XML_RNGValidator::class),
            $this->logger
        );

        $this->event_manager->expects($this->once())->method('processEvent')->with(Event::IMPORT_XML_PROJECT_CARDWALL_DONE, self::anything());

        $cardwall_config_xml_import->import(new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project></project>'));
    }
}
