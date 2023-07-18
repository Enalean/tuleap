<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class XMLImportFieldStrategyArtifactLinkTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var  Tracker_FormElement_Field_ArtifactLink */
    private $field;

    /** @var  PFUser */
    private $submitted_by;

    /** @var  Logger */
    private $logger;

    /** @var Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink */
    private $artlink_strategy;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao
     */
    private $nature_dao;

    /** @var  Artifact */
    private $artifact;

    protected function setUp(): void
    {
        $this->field        = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->submitted_by = \Mockery::spy(\PFUser::class);
        $this->logger       = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->nature_dao   = $this->createStub(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao::class);
        $this->artifact     = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getTrackerId')->andReturns(888);

        $this->artlink_strategy = \Mockery::mock(\Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItShouldWorkWithCompleteMapping(): void
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );

        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value>101</value>
                    <value>100</value>
                  </field_change>');

        $this->nature_dao->method('getTypeByShortname')->willReturn([[]]);
        $this->artlink_strategy->shouldReceive('getLastChangeset')->with($xml_change)->andReturns(null);

        $res          = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
        $expected_res = ["new_values" => '2,1', 'removed_values' => [], 'types' => ['1' => '', '2' => '']];
        $this->assertEquals($expected_res, $res);
    }

    public function testItShouldImportSystemNatures(): void
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );

        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value nature="_is_child">101</value>
                    <value nature="_fixed_in">100</value>
                  </field_change>');

        $this->nature_dao->method('getTypeByShortname')->willReturn([[]]);
        $this->artlink_strategy->shouldReceive('getLastChangeset')->with($xml_change)->andReturns(null);

        $res          = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
        $expected_res = ["new_values" => '2,1', 'removed_values' => [], 'types' => ['1' => '_fixed_in', '2' => '_is_child']];

        $this->assertEquals($expected_res, $res);
    }

    public function testItShouldWorkWithCompleteMappingAndNature(): void
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $mapping->add(102, 3);

        $strategy   = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value nature="toto">101</value>
                    <value nature="titi">100</value>
                    <value>102</value>
                  </field_change>');

        $this->artlink_strategy->shouldReceive('getLastChangeset')->with($xml_change)->andReturns(null);
        $this->nature_dao->method('getTypeByShortname')->willReturn([['titi']]);

        $res          = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
        $expected_res = ["new_values" => '2,1,3', 'removed_values' => [], 'types' => ['1' => 'titi', '2' => 'toto', '3' => '']];
        $this->assertEquals($expected_res, $res);
    }

    public function testItShouldLogWhenArtifactLinkReferenceIsBroken(): void
    {
        $mapping    = new Tracker_XML_Importer_ArtifactImportedMapping();
        $strategy   = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value>101</value>
                  </field_change>');

        $this->nature_dao->method('getTypeByShortname')->willReturn([[]]);
        $this->artlink_strategy->shouldReceive('getLastChangeset')->with($xml_change)->andReturns(null);

        $this->logger->shouldReceive('error')->once();
        $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
    }

    public function testItShouldRemoveValuesWhenArtifactChildrenAreRemoved(): void
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(200, 1);
        $mapping->add(101, 2);
        $mapping->add(102, 3);

        $strategy   = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value nature="toto">200</value>
                  </field_change>');

        $changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_value->shouldReceive('getArtifactIds')->andReturns([1, 2, 3]);
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValues')->andReturns([$changeset_value]);
        $this->artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        $this->nature_dao->method('getTypeByShortname')->willReturn([['toto']]);
        $res          = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
        $expected_res = ["new_values" => '1', 'removed_values' => [2 => 2, 3 => 3], 'types' => ['1' => 'toto']];

        $this->assertEquals($expected_res, $res);
    }
}
