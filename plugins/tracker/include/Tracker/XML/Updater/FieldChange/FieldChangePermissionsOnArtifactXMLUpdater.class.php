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

class Tracker_XML_Updater_FieldChange_FieldChangePermissionsOnArtifactXMLUpdater implements Tracker_XML_Updater_FieldChange_FieldChangeXMLUpdater
{

    /**
     * @param mixed            $submitted_value
     */
    public function update(SimpleXMLElement $field_change_xml, $submitted_value)
    {
        $this->removeExistingUgroupNodes($field_change_xml);

        $field_change_xml['use_perm'] = (int) $submitted_value['use_artifact_permissions'];

        if (isset($submitted_value['u_groups'])) {
            array_walk(
                $submitted_value['u_groups'],
                function ($ugroup_id, $index, SimpleXMLElement $field_xml) {
                    $this->appendUgroupToFieldChangeNode($ugroup_id, $index, $field_xml);
                },
                $field_change_xml
            );
        }
    }

    private function appendUgroupToFieldChangeNode(
        $ugroup_id,
        $index,
        SimpleXMLElement $field_xml
    ) {
        $node = $field_xml->addChild('ugroup');
        $node->addAttribute('ugroup_id', $ugroup_id);
    }

    private function removeExistingUgroupNodes(SimpleXMLElement $field_change_xml)
    {
        unset($field_change_xml->ugroup);
    }
}
