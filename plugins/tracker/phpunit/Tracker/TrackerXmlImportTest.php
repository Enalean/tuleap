<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use ForgeConfig;
use Logger;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use Tracker_CannedResponseFactory;
use Tracker_FormElementFactory;
use Tracker_ReportFactory;
use Tracker_RuleFactory;
use Tracker_SemanticFactory;
use Tracker_Workflow_Trigger_RulesManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\XML\MappingsRegistry;
use WorkflowFactory;
use XML_RNGValidator;

class TrackerXmlImportTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    /**
     * @var string
     */
    private $temporary_directory;

    /**
     * @var MappingsRegistry
     */
    private $mapping_registery;
    /**
     * @var \TrackerXmlImport
     */
    private $tracker_xml_importer;
    /**
     * @var \Project
     */
    private $project;

    private $configuration;

    protected function setUp() : void
    {
        $this->temporary_directory = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8));
        mkdir($this->temporary_directory);
        ForgeConfig::set('tmp_dir', $this->temporary_directory);

        $class_parameters = [
            \Mockery::spy(\TrackerFactory::class),
            \Mockery::spy(\EventManager::class),
            \Mockery::spy(HierarchyDAO::class),
            \Mockery::spy(Tracker_CannedResponseFactory::class),
            \Mockery::spy(Tracker_FormElementFactory::class),
            \Mockery::spy(Tracker_SemanticFactory::class),
            \Mockery::spy(Tracker_RuleFactory::class),
            \Mockery::spy(Tracker_ReportFactory::class),
            \Mockery::spy(WorkflowFactory::class),
            new XML_RNGValidator(),
            \Mockery::spy(Tracker_Workflow_Trigger_RulesManager::class),
            \Mockery::spy(\Tracker_Artifact_XMLImport::class),
            \Mockery::spy(\User\XML\Import\IFindUserFromXMLReference::class),
            \Mockery::spy(\UGroupManager::class),
            \Mockery::spy(Logger::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class),
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class)
        ];

        $this->tracker_xml_importer = \Mockery::mock(\TrackerXmlImport::class, $class_parameters)
                                              ->makePartial()
                                              ->shouldAllowMockingProtectedMethods();

        $this->tracker_xml_importer->shouldReceive('createFromXML')->andReturn(\Mockery::spy(\Tracker::class));

        $this->project           = \Mockery::spy(\Project::class);
        $this->mapping_registery = new MappingsRegistry();
        $this->configuration     = new ImportConfig();
    }

    protected function tearDown() : void
    {
        $folders = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->temporary_directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($folders as $folder) {
            if ($folder->isDir()) {
                rmdir($folder->getPathname());
            } else {
                unlink($folder->getPathname());
            }
        }
        rmdir($this->temporary_directory);
    }

    public function testItShouldRaiseExceptionWithEmptyTrackerDescription()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name>shortname</item_name>
                        <description><![CDATA[]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldRaiseExceptionWithOnlyWhitespacesTrackerDescription()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name>shortname</item_name>
                        <description><![CDATA[              ]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldRaiseExceptionWithEmptyTrackerName()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[]]></name>
                        <item_name>shortname</item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldRaiseExceptionWithOnlyWhitespacesTrackerName()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[              ]]></name>
                        <item_name>shortname</item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldRaiseExceptionWithEmptyTrackerShortName()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name></item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldRaiseExceptionWithInvalidTrackerShortName()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name>-------------</item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItAllowsItemsLabelToHavePlusCharacter()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name><![CDATA[ShortName]]></item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                        <formElements>
                            <formElement type="sb" ID="F1685" rank="4" required="0">
                                <name>status</name>
                                <label><![CDATA[Status]]></label>
                                <bind type="static" is_rank_alpha="0">
                                    <items>
                                        <item ID="V2064" label="Code review" is_hidden="0"/>
                                        <item ID="V2065" label="Code review+" is_hidden="0"/>
                                    </items>
                                    <default_values>
                                        <value REF="V2064"/>
                                    </default_values>
                                </bind>
                            </formElement>
                        </formElements>
                    </tracker>
                </trackers>
            </project>'
        );

        $expected_tracker_mapping = ['T101' => null];

        $created_trackers_mapping = $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $xml_input,
            $this->mapping_registery,
            ''
        );

        $this->assertEquals($expected_tracker_mapping, $created_trackers_mapping);
    }
}
