<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;
use Tracker_FormElement_Field;
use Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter;

require_once __DIR__ . '/../../../../bootstrap.php';

class ChangesetValuePermissionsOnArtifactXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_FormElement_Field */
    private $field;

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
        $changeset_value = \Mockery::spy(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact::class);
        $changeset_value->shouldReceive([
            'getUgroupNamesFromPerms' => ['ug01', 'ug02'],
            'getPerms'                => [101, 102],
            'getUsed'                 => true,
            'getField'                => $this->field,
        ]);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(Tracker_Artifact::class),
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
        $changeset_value = \Mockery::spy(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact::class);
        $changeset_value->shouldReceive([
            'getUgroupNamesFromPerms' => ['ug01', null],
            'getPerms'                => [101, null],
            'getUsed'                 => true,
            'getField'                => $this->field,
        ]);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(Tracker_Artifact::class),
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
