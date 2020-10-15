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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter extends Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter
{
    /**
     * @var FieldChangeListBuilder
     */
    private $field_change_list_builder;

    public function __construct(FieldChangeListBuilder $field_change_list_builder)
    {
        $this->field_change_list_builder = $field_change_list_builder;
    }

    protected function getFieldChangeType()
    {
        return 'list';
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        $bind_type = $changeset_value->getField()->getBind()->getType();
        $values    = $changeset_value->getValue();

        $this->field_change_list_builder->build(
            $changeset_xml,
            $changeset_value->getField()->getName(),
            $bind_type,
            $values
        );
    }
}
