<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use Tuleap\Tracker\Artifact\ChangesetValueComputed;

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class ChangesetValueComputedXMLExporterTest extends \TuleapTestCase
{
    /**
     * @var \Tracker_Artifact
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

    public function setUp()
    {
        parent::setUp();
        $this->artifact            = mock('Tracker_Artifact');
        $this->field               = mock('Tracker_FormElement_Field_Computed');
        $this->field               = stub($this->field)->getName()->returns('capacity');
        $this->artifact_xml        = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_value_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->changeset           = mock('Tracker_Artifact_Changeset');
    }

    public function itCreatesAComputedNode()
    {
        $exporter        = new ChangesetValueComputedXMLExporter();
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 1);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEqual($field_change['type'], 'computed');
        $this->assertEqual($field_change['field_name'], $this->field->getName());
    }

    public function itExportsAFieldInAutocomputeMode()
    {
        $exporter        = new ChangesetValueComputedXMLExporter();
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, null);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertTrue($field_change->is_autocomputed);
        $this->assertFalse(isset($field_change->manual_value));
    }

    public function itExportsFieldWithAManualValue()
    {
        $exporter        = new ChangesetValueComputedXMLExporter();
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 1.5);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEqual($field_change->manual_value, 1.5);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }

    public function itExportsFieldWithAManualValueSetTo0()
    {
        $exporter        = new ChangesetValueComputedXMLExporter();
        $changeset_value = new ChangesetValueComputed(1, $this->changeset, $this->field, true, 0);

        $exporter->export($this->artifact_xml, $this->changeset_value_xml, $this->artifact, $changeset_value);

        $field_change = $this->changeset_value_xml->field_change;
        $this->assertEqual($field_change->manual_value, 0);
        $this->assertFalse(isset($field_change->is_autocomputed));
    }
}
