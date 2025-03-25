<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\TestManagement\XML;

use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinition;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueXMLExporter;

class TrackerXMLExporterChangesetValueStepDefinitionXMLExporter extends ChangesetValueXMLExporter
{
    public function __construct(private StepXMLExporter $step_xml_exporter)
    {
    }

    protected function getFieldChangeType(): string
    {
        return StepDefinition::TYPE;
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value,
    ): void {
        $values = $changeset_value->getValue();
        $field  = $changeset_value->getField();
        if (! count($values) > 0) {
            return;
        }
        $field_change = $changeset_xml->addChild('external_field_change');
        $field_change->addAttribute('field_name', $field->getName());
        $field_change->addAttribute('type', $this->getFieldChangeType());
        foreach ($values as $value) {
            $this->step_xml_exporter->exportStepInFieldChange($value, $field_change);
        }
    }
}
