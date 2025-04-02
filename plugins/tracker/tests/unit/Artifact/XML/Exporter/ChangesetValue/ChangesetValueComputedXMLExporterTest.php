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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_Computed;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValueComputed;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueComputedXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Artifact&MockObject $artifact;

    private Tracker_FormElement_Field_Computed&MockObject $field;

    private SimpleXMLElement $artifact_xml;

    private SimpleXMLElement $changeset_value_xml;

    private Tracker_Artifact_Changeset&MockObject $changeset;
    private PFUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact            = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->field               = $this->createMock(\Tracker_FormElement_Field_Computed::class);
        $this->artifact_xml        = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_value_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->changeset           = $this->createMock(\Tracker_Artifact_Changeset::class);
        $this->user                = new PFUser(['user_id' => 101, 'language_id' => 'en']);

        $this->field->method('getName')->willReturn('capacity');
    }

    public function testItCreatesAComputedNode(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, false);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 1, false);

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value, []);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals('computed', $field_change['type']);
        $this->assertEquals($this->field->getName(), $field_change['field_name']);
    }

    public function testItExportsAFieldInAutocomputeMode(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, false);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, null, false);

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value, []);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals('1', $field_change->is_autocomputed);
        $this->assertFalse(isset($field_change->manual_value));
    }

    public function testItExportsLastChangesetAsAManualValueInArchiveMode(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, null, false);

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->artifact->method('getChangesets')->willReturn([$this->changeset]);
        $this->changeset->method('getValue')->willReturn($changeset_value);
        $this->field->method('getComputedValue')->willReturn(1.5);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value, []);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(1.5, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }

    public function testItExportsLastChangesetAsAManualValueInArchiveModeIfLastChangesetSwitchToManualValue(): void
    {
        $exporter                 = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value          = new ChangesetValueComputed(2, $this->changeset, $this->field, true, 1.5, true);
        $previous_changeset       = $this->createMock(Tracker_Artifact_Changeset::class);
        $previous_changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, null, false);

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->artifact->method('getPreviousChangeset')->willReturn($previous_changeset);
        $this->artifact->method('getChangesets')->willReturn([$previous_changeset, $this->changeset]);
        $this->changeset->method('getValue')->willReturn($changeset_value);
        $this->changeset->method('getId')->willReturn(2);
        $previous_changeset->method('getValue')->willReturn($previous_changeset_value);
        $previous_changeset->method('getId')->willReturn(1);
        $this->field->method('getComputedValue')->willReturn(1.5);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value, []);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(1.5, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }

    public function testItExportsLastChangesetAsAManualValueInArchiveModeIfThereIsNoPreviousChangesetValue(): void
    {
        $exporter           = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value    = new ChangesetValueComputed(2, $this->changeset, $this->field, true, 1.5, true);
        $previous_changeset = $this->createMock(Tracker_Artifact_Changeset::class);

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->artifact->method('getPreviousChangeset')->willReturn($previous_changeset);
        $this->artifact->method('getChangesets')->willReturn([$previous_changeset, $this->changeset]);
        $this->changeset->method('getValue')->willReturn($changeset_value);
        $this->changeset->method('getId')->willReturn(2);
        $previous_changeset->method('getValue')->willReturn(null);
        $previous_changeset->method('getId')->willReturn(1);
        $this->field->method('getComputedValue')->willReturn(1.5);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value, []);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(1.5, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }

    public function testItOnlyExportsLastChangesetAsAManualValueInArchiveMode(): void
    {
        $current_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $exporter          = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value   = new ChangesetValueComputed(1, $current_changeset, $this->field, true, null, false);

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->artifact->method('getChangesets')->willReturn([$current_changeset, $this->changeset]);
        $this->changeset->method('getValue')->willReturn(null);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value, []);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals('1', (string) $field_change->is_autocomputed);
        $this->assertFalse(isset($field_change->manual_value));
    }

    public function testItDoesNotExportLastChangesetInArchiveModeIfAlreadyInManualMode(): void
    {
        $previous_changeset       = $this->createMock(Tracker_Artifact_Changeset::class);
        $exporter                 = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value          = new ChangesetValueComputed(2, $this->changeset, $this->field, true, 1, true);
        $previous_changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 1, true);

        $this->artifact->method('getId')->willReturn(1);
        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->artifact->method('getChangesets')->willReturn([$previous_changeset, $this->changeset]);
        $this->artifact->method('getPreviousChangeset')->willReturn($previous_changeset);
        $this->changeset->method('getValue')->willReturn($changeset_value);
        $this->changeset->method('getId')->willReturn(2);
        $previous_changeset->method('getValue')->willReturn($previous_changeset_value);
        $previous_changeset->method('getId')->willReturn(1);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value, []);

        $this->assertFalse(isset($this->changeset_value_xml->field_change));
    }

    public function testItExportsFieldWithAManualValue(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, false);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 1.5, true);

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value, []);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(1.5, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }

    public function testItExportsFieldWithAManualValueSetTo0(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, false);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 0, true);

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value, []);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(0, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }
}
