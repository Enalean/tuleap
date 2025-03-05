<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Exporter\ChangesetValue;

use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;
use Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValuePermissionsOnArtifactXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter $exporter;

    private SimpleXMLElement $changeset_xml;

    private SimpleXMLElement $artifact_xml;

    private \Tracker_FormElement_Field_PermissionsOnArtifact $field;

    public function setUp(): void
    {
        parent::setUp();

        $this->field = new \Tracker_FormElement_Field_PermissionsOnArtifact(
            1,
            101,
            null,
            'perms',
            'Permissions on artifacts',
            'description',
            1,
            'P',
            false,
            false,
            1
        );

        $this->exporter      = new Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter();
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
    }

    public function testItCreatesFieldChangeNodeInChangesetNode()
    {
        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact::class);
        $changeset_value->method('getUgroupNamesFromPerms')->willReturn(['ug01', 'ug02']);
        $changeset_value->method('getPerms')->willReturn([101, 102]);
        $changeset_value->method('getUsed')->willReturn(true);
        $changeset_value->method('getField')->willReturn($this->field);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
            $changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEquals((string) $field_change['type'], 'permissions_on_artifact');
        $this->assertEquals((string) $field_change['field_name'], 'perms');
        $this->assertEquals((string) $field_change['use_perm'], '1');
        $this->assertEquals(count($field_change->ugroup), 2);
        $this->assertEquals((string) $field_change->ugroup[0]['ugroup_name'], 'ug01');
        $this->assertEquals((string) $field_change->ugroup[1]['ugroup_name'], 'ug02');
    }

    public function testItDoesNotAddEmptyUgroupIfASelectedUgroupHAsBeenDeleted()
    {
        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact::class);
        $changeset_value->method('getUgroupNamesFromPerms')->willReturn(['ug01', null]);
        $changeset_value->method('getPerms')->willReturn([101, null]);
        $changeset_value->method('getUsed')->willReturn(true);
        $changeset_value->method('getField')->willReturn($this->field);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
            $changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEquals((string) $field_change['type'], 'permissions_on_artifact');
        $this->assertEquals((string) $field_change['field_name'], 'perms');
        $this->assertEquals((string) $field_change['use_perm'], '1');
        $this->assertEquals(count($field_change->ugroup), 1);
        $this->assertEquals((string) $field_change->ugroup[0]['ugroup_name'], 'ug01');
    }
}
