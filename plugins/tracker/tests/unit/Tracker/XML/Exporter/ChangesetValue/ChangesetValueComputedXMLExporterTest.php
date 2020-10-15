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

declare(strict_types=1);

namespace Tuleap\Tracker\XML\Exporter\ChangesetValue;

use PFUser;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Artifact\ChangesetValueComputed;

final class ChangesetValueComputedXMLExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    /**
     * @var \Tracker_FormElement_Field_Computed
     */
    private $field;

    /**
     * @var SimpleXMLElement
     */
    private $artifact_xml;

    /**
     * @var SimpleXMLElement
     */
    private $changeset_value_xml;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact            = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->field               = \Mockery::spy(\Tracker_FormElement_Field_Computed::class);
        $this->field               = $this->field->shouldReceive('getName')->andReturns('capacity')->getMock();
        $this->artifact_xml        = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_value_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->changeset           = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $this->user                = new PFUser(['user_id' => 101, 'language_id' => 'en']);
    }

    public function testItCreatesAComputedNode(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, false);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 1, false);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals('computed', $field_change['type']);
        $this->assertEquals($this->field->getName(), $field_change['field_name']);
    }

    public function testItExportsAFieldInAutocomputeMode(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, false);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, null, false);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals('1', $field_change->is_autocomputed);
        $this->assertFalse(isset($field_change->manual_value));
    }

    public function testItExportsLastChangesetAsAManualValueInArchiveMode(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, null, false);

        $this->artifact->shouldReceive('getLastChangeset')->andReturns($this->changeset);
        $this->artifact->shouldReceive('getChangesets')->andReturns([$this->changeset]);
        $this->changeset->shouldReceive('getValue')->andReturns($changeset_value);
        $this->field->shouldReceive('getComputedValue')->andReturns(1.5);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(1.5, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }

    public function testItExportsLastChangesetAsAManualValueInArchiveModeIfLastChangesetSwitchToManualValue(): void
    {
        $exporter                 = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value          = new ChangesetValueComputed(2, $this->changeset, $this->field, true, 1.5, true);
        $previous_changeset       = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $previous_changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, null, false);

        $this->artifact->shouldReceive('getLastChangeset')->andReturns($this->changeset);
        $this->artifact->shouldReceive('getPreviousChangeset')->andReturns($previous_changeset);
        $this->artifact->shouldReceive('getChangesets')->andReturns([$previous_changeset, $this->changeset]);
        $this->changeset->shouldReceive('getValue')->andReturns($changeset_value);
        $previous_changeset->shouldReceive('getValue')->andReturns($previous_changeset_value);
        $this->field->shouldReceive('getComputedValue')->andReturns(1.5);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(1.5, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }

    public function testItExportsLastChangesetAsAManualValueInArchiveModeIfThereIsNoPreviousChangesetValue(): void
    {
        $exporter           = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value    = new ChangesetValueComputed(2, $this->changeset, $this->field, true, 1.5, true);
        $previous_changeset = \Mockery::spy(Tracker_Artifact_Changeset::class);

        $this->artifact->shouldReceive('getLastChangeset')->andReturns($this->changeset);
        $this->artifact->shouldReceive('getPreviousChangeset')->andReturns($previous_changeset);
        $this->artifact->shouldReceive('getChangesets')->andReturns([$previous_changeset, $this->changeset]);
        $this->changeset->shouldReceive('getValue')->andReturns($changeset_value);
        $previous_changeset->shouldReceive('getValue')->andReturns(null);
        $this->field->shouldReceive('getComputedValue')->andReturns(1.5);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(1.5, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }

    public function testItOnlyExportsLastChangesetAsAManualValueInArchiveMode(): void
    {
        $current_changeset = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $exporter          = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value   = new ChangesetValueComputed(1, $current_changeset, $this->field, true, null, false);

        $this->artifact->shouldReceive('getLastChangeset')->andReturns($this->changeset);
        $this->artifact->shouldReceive('getChangesets')->andReturns([$current_changeset, $this->changeset]);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals('1', (string) $field_change->is_autocomputed);
        $this->assertFalse(isset($field_change->manual_value));
    }

    public function testItDoesNotExportLastChangesetInArchiveModeIfAlreadyInManualMode(): void
    {
        $previous_changeset       = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $exporter                 = new ChangesetValueComputedXMLExporter($this->user, true);
        $changeset_value          = new ChangesetValueComputed(2, $this->changeset, $this->field, true, 1, true);
        $previous_changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 1, true);

        $this->artifact->shouldReceive('getLastChangeset')->andReturns($this->changeset);
        $this->artifact->shouldReceive('getChangesets')->andReturns([$previous_changeset, $this->changeset]);
        $this->artifact->shouldReceive('getPreviousChangeset')->andReturns($previous_changeset);
        $this->changeset->shouldReceive('getValue')->andReturns($changeset_value);
        $previous_changeset->shouldReceive('getValue')->andReturns($previous_changeset_value);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $this->assertFalse(isset($this->changeset_value_xml->field_change));
    }

    public function testItExportsFieldWithAManualValue(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, false);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 1.5, true);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(1.5, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }

    public function testItExportsFieldWithAManualValueSetTo0(): void
    {
        $exporter        = new ChangesetValueComputedXMLExporter($this->user, false);
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 0, true);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEquals(0, (float) $field_change->manual_value);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }
}
