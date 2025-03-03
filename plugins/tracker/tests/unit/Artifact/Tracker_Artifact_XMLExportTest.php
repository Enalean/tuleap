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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_Artifact_XMLExport;
use Tracker_Artifact_XMLExportTooManyArtifactsException;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetCommentTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Artifact_XMLExportTest extends TestCase
{
    private UserManager&MockObject $user_manager;
    private Tracker_FormElementFactory&MockObject $formelement_factory;

    protected function setUp(): void
    {
        $this->user_manager = $this->createMock(UserManager::class);
        UserManager::setInstance($this->user_manager);

        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();
    }

    public function testItExportsArtifactsInXML(): void
    {
        $this->user_manager->method('getUserById')->willReturnCallback(static fn(int $id) => match ($id) {
            101 => UserTestBuilder::aUser()->withId(101)->withUserName('user_01')->withLdapId('ldap_01')->build(),
            102 => UserTestBuilder::aUser()->withId(102)->withUserName('user_02')->withLdapId('ldap_02')->build(),
        });

        $this->formelement_factory->method('getUsedFileFields')->willReturn([]);

        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $timestamp_01 = '1433863107';
        $timestamp_02 = '1433949507';
        $timestamp_03 = '1434035907';
        $timestamp_04 = '1434122307';

        $text_field_01 = TextFieldBuilder::aTextField(1)->inTracker($tracker)->withName('text_01')->build();
        $text_field_02 = TextFieldBuilder::aTextField(2)->inTracker($tracker)->withName('text_02')->build();

        $methods      = ['getId', 'getSubmittedBy', 'getSubmittedOn', 'getValues', 'getArtifact', 'getComment', 'forceFetchAllValues'];
        $changeset_01 = $this->createPartialMock(Tracker_Artifact_Changeset::class, $methods);
        $changeset_02 = $this->createPartialMock(Tracker_Artifact_Changeset::class, $methods);
        $changeset_03 = $this->createPartialMock(Tracker_Artifact_Changeset::class, $methods);
        $changeset_04 = $this->createPartialMock(Tracker_Artifact_Changeset::class, $methods);

        $value_01 = new Tracker_Artifact_ChangesetValue_Text(1, $changeset_01, $text_field_01, true, 'value_01', 'text');
        $value_02 = new Tracker_Artifact_ChangesetValue_Text(2, $changeset_01, $text_field_01, true, 'value_02', 'text');
        $value_03 = new Tracker_Artifact_ChangesetValue_Text(3, $changeset_02, $text_field_01, true, 'value_03', 'text');
        $value_04 = new Tracker_Artifact_ChangesetValue_Text(4, $changeset_02, $text_field_01, true, 'value_04', 'text');
        $value_05 = new Tracker_Artifact_ChangesetValue_Text(5, $changeset_03, $text_field_02, true, 'value_05', 'text');
        $value_06 = new Tracker_Artifact_ChangesetValue_Text(6, $changeset_03, $text_field_02, true, 'value_06', 'text');
        $value_07 = new Tracker_Artifact_ChangesetValue_Text(7, $changeset_04, $text_field_02, true, 'value_07', 'text');

        $changeset_01->method('getId')->willReturn(10001);
        $changeset_01->method('getSubmittedBy')->willReturn(101);
        $changeset_01->method('getSubmittedOn')->willReturn($timestamp_01);
        $changeset_01->method('getValues')->willReturn([$value_01, $value_02]);

        $changeset_02->method('getId')->willReturn(10002);
        $changeset_02->method('getSubmittedBy')->willReturn(101);
        $changeset_02->method('getSubmittedOn')->willReturn($timestamp_02);
        $changeset_02->method('getValues')->willReturn([$value_03, $value_04]);

        $changeset_03->method('getId')->willReturn(10003);
        $changeset_03->method('getSubmittedBy')->willReturn(101);
        $changeset_03->method('getSubmittedOn')->willReturn($timestamp_03);
        $changeset_03->method('getValues')->willReturn([$value_05, $value_06]);

        $changeset_04->method('getId')->willReturn(10004);
        $changeset_04->method('getSubmittedBy')->willReturn(102);
        $changeset_04->method('getSubmittedOn')->willReturn($timestamp_04);
        $changeset_04->method('getValues')->willReturn([$value_07]);

        $artifact_01 = $this->buildArtifact(101, $tracker, [$changeset_01, $changeset_02]);
        $artifact_02 = $this->buildArtifact(102, $tracker, [$changeset_03, $changeset_04]);

        $changeset_01->method('getArtifact')->willReturn($artifact_01);
        $changeset_02->method('getArtifact')->willReturn($artifact_01);
        $changeset_03->method('getArtifact')->willReturn($artifact_02);
        $changeset_04->method('getArtifact')->willReturn($artifact_02);

        $comment_01 = ChangesetCommentTestBuilder::aComment()->withCommentBody('<b> My comment 01</b>')->build();
        $comment_02 = ChangesetCommentTestBuilder::aComment()->withCommentBody('<b> My comment 02</b>')->build();
        $comment_03 = ChangesetCommentTestBuilder::aComment()->withCommentBody('<b> My comment 03</b>')->build();
        $comment_04 = ChangesetCommentTestBuilder::aComment()->withCommentBody('<b> My comment 04</b>')->build();

        $changeset_01->method('getComment')->willReturn($comment_01);
        $changeset_02->method('getComment')->willReturn($comment_02);
        $changeset_03->method('getComment')->willReturn($comment_03);
        $changeset_04->method('getComment')->willReturn($comment_04);

        $rng_validator    = new XML_RNGValidator();
        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->method('getArtifactsByTrackerId')->with(101)->willReturn([$artifact_01, $artifact_02]);
        $user_xml_exporter        = new UserXMLExporter($this->user_manager, $this->createMock(UserXMLExportedCollection::class));
        $external_field_extractor = $this->createMock(ExternalFieldsExtractor::class);

        $exporter = new Tracker_Artifact_XMLExport(
            $rng_validator,
            $artifact_factory,
            true,
            $user_xml_exporter,
            $external_field_extractor
        );

        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $external_field_extractor->method('extractExternalFieldsFromArtifact');

        $admin_user = UserTestBuilder::buildSiteAdministrator();

        $archive = $this->createMock(ArchiveInterface::class);

        $exporter->export($tracker, $xml_element, $admin_user, $archive);

        self::assertNotNull($xml_element->artifacts);

        self::assertEquals('101', (string) $xml_element->artifacts->artifact[0]['id']);
        self::assertEquals('102', (string) $xml_element->artifacts->artifact[1]['id']);

        self::assertNotNull($xml_element->artifacts->artifact[0]->changeset);
        self::assertCount(2, $xml_element->artifacts->artifact[0]->changeset);
        self::assertNotNull($xml_element->artifacts->artifact[1]->changeset);
        self::assertCount(2, $xml_element->artifacts->artifact[1]->changeset);
    }

    private function buildArtifact(int $id, Tracker $tracker, array $changesets): Artifact
    {
        $artifact = new Artifact($id, $tracker->getId(), 101, 10, false);
        $artifact->setChangesets($changesets);
        $artifact->setTracker($tracker);
        $artifact->setFormElementFactory($this->formelement_factory);

        return $artifact;
    }

    public function testItRaisesAnExceptionWhenThresholdIsReached(): void
    {
        $rng_validator    = new XML_RNGValidator();
        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->method('getArtifactsByTrackerId')->willReturn(array_fill(0, Tracker_Artifact_XMLExport::THRESHOLD + 1, null));

        $external_field_extractor = $this->createMock(ExternalFieldsExtractor::class);

        $exporter = new Tracker_Artifact_XMLExport(
            $rng_validator,
            $artifact_factory,
            false,
            $this->createMock(UserXMLExporter::class),
            $external_field_extractor
        );

        $external_field_extractor->method('extractExternalFieldsFromArtifact');

        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $archive = $this->createMock(ArchiveInterface::class);

        $tracker = TrackerTestBuilder::aTracker()->withId(888)->build();

        $this->expectException(Tracker_Artifact_XMLExportTooManyArtifactsException::class);
        $exporter->export($tracker, $xml_element, UserTestBuilder::buildWithDefaults(), $archive);
    }
}
