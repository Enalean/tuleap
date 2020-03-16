<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

class ArtifactPermissionsXMLExporter
{
    /** @var ArtifactXMLNodeHelper */
    private $node_helper;

    /** @var ArtifactXMLExporterDao */
    private $dao;

    public function __construct(ArtifactXMLNodeHelper $node_helper, ArtifactXMLExporterDao $dao)
    {
        $this->node_helper = $node_helper;
        $this->dao         = $dao;
    }

    public function appendNode(DOMElement $artifact_node, $artifact_id)
    {
        $list_of_changesets = $artifact_node->getElementsByTagName('changeset');
        if ($list_of_changesets->length == 0) {
            return;
        }

        $permissions = $this->getFilteredUgroupIds($artifact_id);
        if (! count($permissions)) {
            return;
        }

        $last_changeset_node = $list_of_changesets->item($list_of_changesets->length - 1);
        $field_node = $this->node_helper->createElement('field_change');
        $field_node->setAttribute('field_name', 'permissions_on_artifact');
        $field_node->setAttribute('type', 'permissions_on_artifact');
        $field_node->setAttribute('use_perm', '1');
        foreach ($permissions as $ugroup_id) {
            $ugroup_node = $this->node_helper->createElement('ugroup');
            $ugroup_node->setAttribute('ugroup_id', $ugroup_id);
            $field_node->appendChild($ugroup_node);
        }

        $last_changeset_node->appendChild($field_node);
    }

    /**
     * @return array
     */
    private function getFilteredUgroupIds($artifact_id)
    {
        $permissions         = $this->dao->searchPermsForArtifact($artifact_id);
        $filtered_ugroup_ids = array();

        foreach ($permissions as $row) {
            $ugroup_id = (int) $row['ugroup_id'];

            if ($ugroup_id === ProjectUGroup::NONE) {
                $ugroup_id = ProjectUGroup::PROJECT_ADMIN;
            }

            $filtered_ugroup_ids[] = $ugroup_id;
        }

        return array_unique($filtered_ugroup_ids);
    }
}
