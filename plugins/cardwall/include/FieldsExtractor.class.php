<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


/**
 * Foreach artifact in a TreeNode tree, make a collection of the semantic status fields and
 * index them by their id
 */
class Cardwall_FieldsExtractor
{

    /**
     * @var Cardwall_FieldProviders_IProvideFieldGivenAnArtifact
     */
    private $field_provider;

    public function __construct(Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider)
    {
        $this->field_provider = $field_provider;
    }
    public function extractAndIndexFieldsOf(TreeNode $node)
    {
        $artifacts = $this->getArtifactsFromSecondLevelAndDown($node);
        return $this->getIndexedStatusFieldsOf($artifacts);
    }

    private function getArtifactsFromSecondLevelAndDown(TreeNode $root_node)
    {
        $leafs = [];
        foreach ($root_node->getChildren() as $child) {
            $leafs = array_merge($leafs, $child->flattenChildren());
        }
        $artifacts  = [];
        foreach ($leafs as $node) {
            $this->appendIfArtifactNode($artifacts, $node);
        }
        return $artifacts;
    }

    private function appendIfArtifactNode(array &$artifacts, TreeNode $node)
    {
        if ($node instanceof ArtifactNode) {
            $artifacts[] = $node->getArtifact();
        }
    }

    private function getIndexedStatusFieldsOf(array $artifacts)
    {
        $trackers = [];
        foreach ($artifacts as $artifact) {
            $trackers[] = $artifact->getTracker();
        }
        $status_fields          = array_filter(array_map([$this->field_provider, 'getField'], $trackers));
        $indexed_status_fields  = $this->indexById($status_fields);
        return $indexed_status_fields;
    }

    private function indexById(array $fields)
    {
        $indexed_array = [];
        foreach ($fields as $field) {
            $indexed_array[$field->getId()] = $field;
        }
        return $indexed_array;
    }
}
