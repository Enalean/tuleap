<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Tuleap\Project\XML\Import\ExternalFieldsExtractor;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Artifact_XMLExportTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $formelement_factory;

    protected function setUp(): void
    {
        $this->user_manager = \Mockery::spy(\UserManager::class);
        UserManager::setInstance($this->user_manager);

        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();
    }

    public function testItExportsArtifactsInXML(): void
    {
        $user_01 = new PFUser([
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'user_01',
            'ldap_id' => 'ldap_O1'
        ]);

        $user_02 = new PFUser([
            'user_id' => 102,
            'language_id' => 'en',
            'user_name' => 'user_02',
            'ldap_id' => 'ldap_O2'
        ]);

        $this->user_manager->shouldReceive('getUserById')->with(101)->andReturns($user_01);
        $this->user_manager->shouldReceive('getUserById')->with(102)->andReturns($user_02);

        $this->formelement_factory->shouldReceive('getUsedFileFields')->andReturns(array());

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $timestamp_01 = '1433863107';
        $timestamp_02 = '1433949507';
        $timestamp_03 = '1434035907';
        $timestamp_04 = '1434122307';

        $text_field_01 = \Mockery::spy(\Tracker_FormElement_Field_Text::class)->shouldReceive('getName')->andReturns('text_01')->getMock();
        $text_field_01->shouldReceive('getTracker')->andReturns($tracker);
        $text_field_02 = \Mockery::spy(\Tracker_FormElement_Field_Text::class)->shouldReceive('getName')->andReturns('text_02')->getMock();
        $text_field_02->shouldReceive('getTracker')->andReturns($tracker);

        $changeset_01 = \Mockery::mock(\Tracker_Artifact_Changeset::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $changeset_02 = \Mockery::mock(\Tracker_Artifact_Changeset::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $changeset_03 = \Mockery::mock(\Tracker_Artifact_Changeset::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $changeset_04 = \Mockery::mock(\Tracker_Artifact_Changeset::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $value_01 = new Tracker_Artifact_ChangesetValue_Text(1, $changeset_01, $text_field_01, true, 'value_01', 'text');
        $value_02 = new Tracker_Artifact_ChangesetValue_Text(2, $changeset_01, $text_field_01, true, 'value_02', 'text');
        $value_03 = new Tracker_Artifact_ChangesetValue_Text(3, $changeset_02, $text_field_01, true, 'value_03', 'text');
        $value_04 = new Tracker_Artifact_ChangesetValue_Text(4, $changeset_02, $text_field_01, true, 'value_04', 'text');
        $value_05 = new Tracker_Artifact_ChangesetValue_Text(5, $changeset_03, $text_field_02, true, 'value_05', 'text');
        $value_06 = new Tracker_Artifact_ChangesetValue_Text(6, $changeset_03, $text_field_02, true, 'value_06', 'text');
        $value_07 = new Tracker_Artifact_ChangesetValue_Text(7, $changeset_04, $text_field_02, true, 'value_07', 'text');

        $value_dao = \Mockery::spy(\Tracker_Artifact_Changeset_ValueDao::class);
        $value_dao->shouldReceive('searchById')->andReturns(array());

        $changeset_01->shouldReceive('getId')->andReturns(10001);
        $changeset_01->shouldReceive('getSubmittedBy')->andReturns(101);
        $changeset_01->shouldReceive('getSubmittedOn')->andReturns($timestamp_01);
        $changeset_01->shouldReceive('getValues')->andReturns(array($value_01, $value_02));
        $changeset_01->shouldReceive('getValueDao')->andReturns($value_dao);

        $changeset_02->shouldReceive('getId')->andReturns(10002);
        $changeset_02->shouldReceive('getSubmittedBy')->andReturns(101);
        $changeset_02->shouldReceive('getSubmittedOn')->andReturns($timestamp_02);
        $changeset_02->shouldReceive('getValues')->andReturns(array($value_03, $value_04));
        $changeset_02->shouldReceive('getValueDao')->andReturns($value_dao);

        $changeset_03->shouldReceive('getId')->andReturns(10003);
        $changeset_03->shouldReceive('getSubmittedBy')->andReturns(101);
        $changeset_03->shouldReceive('getSubmittedOn')->andReturns($timestamp_03);
        $changeset_03->shouldReceive('getValues')->andReturns(array($value_05, $value_06));
        $changeset_03->shouldReceive('getValueDao')->andReturns($value_dao);

        $changeset_04->shouldReceive('getId')->andReturns(10004);
        $changeset_04->shouldReceive('getSubmittedBy')->andReturns(102);
        $changeset_04->shouldReceive('getSubmittedOn')->andReturns($timestamp_04);
        $changeset_04->shouldReceive('getValues')->andReturns(array($value_07));
        $changeset_04->shouldReceive('getValueDao')->andReturns($value_dao);

        $artifact_01 = $this->buildArtifact(101, $tracker, [$changeset_01, $changeset_02]);
        $artifact_02 = $this->buildArtifact(102, $tracker, [$changeset_03, $changeset_04]);

        $changeset_01->shouldReceive('getArtifact')->andReturns($artifact_01);
        $changeset_02->shouldReceive('getArtifact')->andReturns($artifact_01);
        $changeset_03->shouldReceive('getArtifact')->andReturns($artifact_02);
        $changeset_04->shouldReceive('getArtifact')->andReturns($artifact_02);

        $comment_01 = new Tracker_Artifact_Changeset_Comment(
            1,
            $changeset_01,
            0,
            0,
            101,
            $timestamp_01,
            '<b> My comment 01</b>',
            'html',
            0
        );

        $comment_02 = new Tracker_Artifact_Changeset_Comment(
            2,
            $changeset_02,
            0,
            0,
            101,
            $timestamp_02,
            '<b> My comment 02</b>',
            'html',
            0
        );

        $comment_03 = new Tracker_Artifact_Changeset_Comment(
            3,
            $changeset_03,
            0,
            0,
            102,
            $timestamp_03,
            '<b> My comment 03</b>',
            'html',
            0
        );

        $comment_04 = new Tracker_Artifact_Changeset_Comment(
            4,
            $changeset_04,
            0,
            0,
            102,
            $timestamp_04,
            '<b> My comment 04</b>',
            'html',
            0
        );

        $changeset_01->shouldReceive('getComment')->andReturns($comment_01);
        $changeset_02->shouldReceive('getComment')->andReturns($comment_02);
        $changeset_03->shouldReceive('getComment')->andReturns($comment_03);
        $changeset_04->shouldReceive('getComment')->andReturns($comment_04);

        $rng_validator    = new XML_RNGValidator();
        $artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class)->shouldReceive('getArtifactsByTrackerId')->with(101)->andReturns(array(
            $artifact_01,
            $artifact_02
        ))->getMock();
        $can_bypass_threshold = true;

        $user_xml_exporter = new UserXMLExporter($this->user_manager, \Mockery::spy(\UserXMLExportedCollection::class));
        $external_field_extractor = Mockery::mock(ExternalFieldsExtractor::class);

        $exporter = new Tracker_Artifact_XMLExport(
            $rng_validator,
            $artifact_factory,
            $can_bypass_threshold,
            $user_xml_exporter,
            $external_field_extractor
        );

        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact');

        $admin_user = \Mockery::spy(\PFUser::class)->shouldReceive('isSuperUser')->andReturns(true)->getMock();

        $archive = \Mockery::spy(\Tuleap\Project\XML\Export\ArchiveInterface::class);

        $exporter->export($tracker, $xml_element, $admin_user, $archive);

        $this->assertNotNull($xml_element->artifacts);

        $this->assertEquals('101', (string) $xml_element->artifacts->artifact[0]['id']);
        $this->assertEquals('102', (string) $xml_element->artifacts->artifact[1]['id']);

        $this->assertNotNull($xml_element->artifacts->artifact[0]->changeset);
        $this->assertCount(2, $xml_element->artifacts->artifact[0]->changeset);
        $this->assertNotNull($xml_element->artifacts->artifact[1]->changeset);
        $this->assertCount(2, $xml_element->artifacts->artifact[1]->changeset);
    }

    private function buildArtifact(int $id, Tracker $tracker, array $changesets): Tracker_Artifact
    {
        $artifact = new Tracker_Artifact($id, $tracker->getId(), 101, 10, false);
        $artifact->setChangesets($changesets);
        $artifact->setTracker($tracker);
        $artifact->setFormElementFactory($this->formelement_factory);

        return $artifact;
    }

    public function testItRaisesAnExceptionWhenThresholdIsReached(): void
    {
        $rng_validator    = new XML_RNGValidator();
        $artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class)->shouldReceive('getArtifactsByTrackerId')->andReturns(array_fill(0, Tracker_Artifact_XMLExport::THRESHOLD + 1, null))->getMock();
        $can_bypass_threshold = false;

        $user_xml_exporter = \Mockery::spy(\UserXMLExporter::class);
        $external_field_extractor = Mockery::mock(ExternalFieldsExtractor::class);

        $exporter = new Tracker_Artifact_XMLExport(
            $rng_validator,
            $artifact_factory,
            $can_bypass_threshold,
            $user_xml_exporter,
            $external_field_extractor
        );

        $external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact');

        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $archive = \Mockery::spy(\Tuleap\Project\XML\Export\ArchiveInterface::class);

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(888);

        $this->expectException(\Tracker_Artifact_XMLExportTooManyArtifactsException::class);
        $exporter->export($tracker, $xml_element, \Mockery::spy(\PFUser::class), $archive);
    }
}
