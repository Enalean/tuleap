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

class Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter extends Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter {

    protected function getFieldChangeType() {
        return 'permissions_on_artifact';
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
        $field_change->addAttribute('use_perm', (int)$changeset_value->getUsed());

        $ugroup_ids = $changeset_value->getPerms();
        array_walk(
            $ugroup_ids,
            array($this, 'appendUgroupToFieldChangeNode'),
            $field_change
        );
    }

    private function appendUgroupToFieldChangeNode(
        $ugroup_id,
        $index,
        SimpleXMLElement $field_xml
    ) {
        $node = $field_xml->addChild('ugroup');
        $node->addAttribute('ugroup_id', $ugroup_id);
    }
}