<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\XML\Exporter;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_Comment;
use Tracker_Artifact_ChangesetValue_Float;
use Tracker_Artifact_ChangesetValue_Integer;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ChangesetXMLExporter $exporter;

    private SimpleXMLElement $artifact_xml;

    private ChangesetValuesXMLExporter&MockObject $values_exporter;

    private array $values;

    private Artifact $artifact;
    private UserManager&MockObject $user_manager;

    /** @var UserXMLExporter */
    private $user_xml_exporter;
    private Tracker_Artifact_ChangesetValue_Integer $int_changeset_value;
    private Tracker_Artifact_ChangesetValue_Float $float_changeset_value;
    private Tracker_Artifact_Changeset&MockObject $changeset;
    private Tracker_Artifact_Changeset_Comment&MockObject $comment;

    protected function setUp(): void
    {
        $this->user_manager      = $this->createMock(\UserManager::class);
        $this->user_xml_exporter = $this->getMockBuilder(\UserXMLExporter::class)
            ->setConstructorArgs([$this->user_manager, new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())])
            ->onlyMethods(['exportUserByMail'])
            ->getMock();
        $this->artifact_xml      = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->values_exporter   = $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValuesXMLExporter::class);
        $this->exporter          = new ChangesetXMLExporter(
            $this->values_exporter,
            $this->user_xml_exporter
        );

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);

        $this->int_changeset_value   = new Tracker_Artifact_ChangesetValue_Integer('*', $changeset, '*', '*', '*');
        $this->float_changeset_value = new Tracker_Artifact_ChangesetValue_Float('*', $changeset, '*', '*', '*');
        $this->values                = [
            $this->int_changeset_value,
            $this->float_changeset_value,
        ];

        $this->artifact  = ArtifactTestBuilder::anArtifact(101)->build();
        $this->changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $this->comment   = $this->createMock(\Tracker_Artifact_Changeset_Comment::class);

        $this->changeset->method('getValues')->willReturn($this->values);
        $this->changeset->method('getArtifact')->willReturn($this->artifact);
        $this->changeset->method('getComment')->willReturn($this->comment);
        $this->changeset->method('getSubmittedBy')->willReturn(101);
        $this->changeset->method('getSubmittedOn')->willReturn(1234567890);
        $this->changeset->method('getId')->willReturn(123);
        $this->changeset->method('forceFetchAllValues');
    }

    public function testItAppendsChangesetNodeToArtifactNode(): void
    {
        $this->values_exporter->method('exportSnapshot');

        $this->exporter->exportWithoutComments($this->artifact_xml, $this->changeset, []);

        $this->assertCount(1, $this->artifact_xml->changeset);
        $this->assertCount(1, $this->artifact_xml->changeset->submitted_by);
        $this->assertCount(1, $this->artifact_xml->changeset->submitted_on);
    }

    public function testItDelegatesTheExportOfValues(): void
    {
        $this->values_exporter->expects($this->once())->method('exportSnapshot')->with($this->artifact_xml, self::anything(), $this->artifact, $this->values);
        $this->comment->expects($this->never())->method('exportToXML');

        $this->exporter->exportWithoutComments($this->artifact_xml, $this->changeset, []);
    }

    public function testItExportsTheComments(): void
    {
        $user = new PFUser([
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'user_01',
            'ldap_id' => 'ldap_01',
        ]);
        $this->user_manager->method('getUserById')->with(101)->willReturn($user);

        $this->values_exporter->expects($this->once())->method('exportChangedFields')->with($this->artifact_xml, self::anything(), $this->artifact, $this->values);
        $this->comment->expects($this->once())->method('exportToXML');

        $this->exporter->exportFullHistory($this->artifact_xml, $this->changeset, []);
    }

    public function testItExportsTheIdOfTheChangeset(): void
    {
        $user = new PFUser([
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'user_01',
            'ldap_id' => 'ldap_01',
        ]);
        $this->user_manager->method('getUserById')->with(101)->willReturn($user);
        $this->values_exporter->method('exportChangedFields');
        $this->comment->method('exportToXML');

        $this->exporter->exportFullHistory($this->artifact_xml, $this->changeset, []);

        $this->assertEquals('CHANGESET_123', (string) $this->artifact_xml->changeset['id']);
    }

    public function testItExportsAnonUser(): void
    {
        $this->user_xml_exporter->expects($this->once())->method('exportUserByMail');

        $this->values_exporter->method('exportChangedFields');

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $changeset->method('getId')->willReturn(101);
        $changeset->method('getValues')->willReturn([]);
        $changeset->method('getSubmittedBy')->willReturn(null);
        $changeset->method('getSubmittedOn')->willReturn(1);
        $changeset->method('getEmail')->willReturn('veloc@example.com');
        $changeset->method('getComment')->willReturn(null);
        $changeset->method('forceFetchAllValues');
        $changeset->method('getArtifact')->willReturn($this->artifact);
        $this->exporter->exportFullHistory($this->artifact_xml, $changeset, []);
    }

    public function testItRemovesNullValuesInChangesetValues(): void
    {
        $value = $this->createMock(\Tracker_Artifact_ChangesetValue::class);

        $this->values_exporter->expects($this->once())->method('exportChangedFields')->with(self::anything(), self::anything(), self::anything(), [101 => $value]);

        $user = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withId(101)->build();
        $this->user_manager->method('getUserById')->with(101)->willReturn($user);

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $changeset->method('getId')->willReturn(741);
        $changeset->method('getSubmittedBy')->willReturn($user->getId());
        $changeset->method('getSubmittedOn')->willReturn(1);
        $changeset->method('getComment')->willReturn(null);
        $changeset->method('forceFetchAllValues');
        $changeset->method('getValues')->willReturn([
            101 => $value,
            102 => null,
        ]);

        $changeset->method('getArtifact')->willReturn($this->artifact);

        $this->exporter->exportFullHistory($this->artifact_xml, $changeset, []);
    }
}
