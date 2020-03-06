<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter extends Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter
{

    /**
     * @var UserXMLExporter
     */
    private $user_xml_exporter;

    public function __construct(UserXMLExporter $user_xml_exporter)
    {
        $this->user_xml_exporter = $user_xml_exporter;
    }

    protected function getFieldChangeType()
    {
        return 'list';
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        $field_change = $this->createFieldChangeNodeInChangesetNode(
            $changeset_value,
            $changeset_xml
        );

        $bind_type = $changeset_value->getField()->getBind()->getType();
        $field_change->addAttribute('bind', $bind_type);

        $values = $changeset_value->getValue();

        if (empty($values)) {
            $field_change->addChild('value');
        } elseif ($bind_type === Tracker_FormElement_Field_List_Bind_Users::TYPE) {
            foreach ($values as $value) {
                $this->user_xml_exporter->exportUserByUserId($value, $field_change, 'value');
            }
        } else {
            array_walk(
                $values,
                function ($value, $index, SimpleXMLElement $field_xml) {
                    $this->appendValueToFieldChangeNode($value, $index, $field_xml);
                },
                $field_change
            );
        }
    }

    private function appendValueToFieldChangeNode(
        $value,
        $index,
        SimpleXMLElement $field_xml
    ) {
        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insertWithAttributes($field_xml, 'value', $value, ['format' => 'id']);
    }
}
