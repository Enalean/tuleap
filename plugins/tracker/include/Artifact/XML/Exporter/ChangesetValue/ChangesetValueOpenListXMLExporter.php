<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue;

use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field_List_OpenValue;
use Tuleap\Tracker\Artifact\Artifact;
use UserXMLExporter;

class ChangesetValueOpenListXMLExporter extends ChangesetValueListXMLExporter
{
    public function __construct(private readonly UserXMLExporter $user_xml_exporter)
    {
    }

    #[\Override]
    protected function getFieldChangeType(): string
    {
        return 'open_list';
    }

    #[\Override]
    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value,
        array $value_mapping,
    ): void {
        $field_change = $this->createFieldChangeNodeInChangesetNode(
            $changeset_value,
            $changeset_xml
        );

        $field     = $changeset_value->getField();
        $bind_type = $field->getBind()->getType();
        $field_change->addAttribute('bind', $bind_type);

        $values = $changeset_value->getValue();

        if (empty($values)) {
            $field_change->addChild('value');
            return;
        }

        foreach ($values as $value) {
            if ($this->isValueAnOpenValue($value)) {
                $open_value_id = substr($value, 1);
                $open_value    = $field->getOpenValueById($open_value_id);
                $label         = $open_value->getLabel();

                $this->appendOpenValueLabelToFieldChangeNode($label, $field_change);
            } else {
                $this->appendValue($value, $field_change, $bind_type, $value_mapping);
            }
        }
    }

    private function appendValue($value, SimpleXMLElement $field_xml, $bind_type, array $value_mapping): void
    {
        if ($bind_type === 'users') {
            $this->appendUserValueToFieldChangeNode($value, $field_xml);
        } else {
            $this->appendValueToFieldChangeNode($value, $field_xml, $value_mapping);
        }
    }

    private function appendUserValueToFieldChangeNode($value, SimpleXMLElement $field_xml): void
    {
        $user_id = $this->getUserIdFromValue($value);

        $this->user_xml_exporter->exportUserByUserId($user_id, $field_xml, 'value');
    }

    private function getUserIdFromValue($value): int
    {
        return (int) substr($value, 1);
    }

    private function appendValueToFieldChangeNode($value, SimpleXMLElement $field_xml, array $value_mapping): void
    {
        $value_without_legacy_bind_letter = $this->getUserIdFromValue($value);
        $key                              = array_search($value_without_legacy_bind_letter, $value_mapping);
        if ($key !== false) {
            $value = $key;
        }

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insertWithAttributes($field_xml, 'value', (string) $value, ['format' => 'id']);
    }

    private function appendOpenValueLabelToFieldChangeNode($value, SimpleXMLElement $field_xml): void
    {
        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insertWithAttributes($field_xml, 'value', $value, ['format' => 'label']);
    }

    private function isValueAnOpenValue($value): bool
    {
        return substr($value, 0, 1) === Tracker_FormElement_Field_List_OpenValue::OPEN_PREFIX;
    }
}
