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

use Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter;
use SimpleXMLElement;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue;
use XML_SimpleXMLCDATAFactory;

class ChangesetValueComputedXMLExporter extends Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter
{

    protected function getFieldChangeType()
    {
        return 'computed';
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

        if ($changeset_value->getValue() === null) {
            $field_change->addChild('is_autocomputed', true);
        } else {
            $cdata_factory = new XML_SimpleXMLCDATAFactory();
            $cdata_factory->insert($field_change, 'manual_value', $changeset_value->getValue());
        }
    }
}
