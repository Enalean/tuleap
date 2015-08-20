<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * MERCHANTABILITY or FITNEsemantic_status FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Trafficlights\REST\v1;

use Tuleap\Trafficlights\Dao as TrafficlightsDao;
use Tracker_ArtifactFactory;
use Tracker_Artifact;
use PFUser;

class ExecutionNodeBuilder {

    /**
     * @var TrafficlightsDao
     */
    private $dao;

    /**
     * @var ArtifactNodeBuilder
     */
    private $artifact_builder;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(ArtifactNodeBuilder $artifact_builder, Tracker_ArtifactFactory $artifact_factory, TrafficlightsDao $dao) {
        $this->artifact_builder = $artifact_builder;
        $this->artifact_factory = $artifact_factory;
        $this->dao              = $dao;
    }

    public function getNodeRepresentation(PFUser $user, Tracker_Artifact $execution_artifact) {
        $execution_representation  = $this->artifact_builder->getNodeRepresentation($user, $execution_artifact);
        $definition_representation = $this->getDefinitionRepresentation($user, $execution_artifact, $execution_representation);
        if ($definition_representation) {
            return $this->mergeExecutionAndDefinition($execution_representation, $definition_representation);
        }
        return $execution_representation;
    }

    private function mergeExecutionAndDefinition(NodeRepresentation $execution, NodeRepresentation $definition) {
        $node = new NodeRepresentation();
        $node->build(
            $definition->id,
            NodeReferenceRepresentation::NATURE_ARTIFACT,
            $definition->url,
            $definition->ref_name,
            $definition->ref_label,
            $definition->color,
            $definition->title,
            $execution->status_semantic,
            $execution->status_label
        );

        $this->mergeLinks($node, $execution, $definition);

        return $node;
    }

    private function mergeLinks(NodeRepresentation $final, NodeRepresentation $execution, NodeRepresentation $definition) {
        $exclude_hash         = array($definition->id => true, $execution->id => true);
        $final->links         = $this->getMergedList($execution->links, $definition->links, $exclude_hash);
        $final->reverse_links = $this->getMergedList($execution->reverse_links, $definition->reverse_links, $exclude_hash);
    }

    private function getMergedList(array $execution_links, array $definition_links, array $exclude_hash) {
        $all = array();
        $this->addUniq($execution_links, $all, $exclude_hash);
        $this->addUniq($definition_links, $all, $exclude_hash);
        return array_values($all);
    }

    private function addUniq(array $links, array &$all_links_hash, array $exclude_hash) {
        foreach ($links as $link) {
            if (! isset($all_links_hash[$link->id]) && ! isset($exclude_hash[$link->id])) {
                $all_links_hash[$link->id] = $link;
            }
        }
    }

    private function getDefinitionRepresentation(PFUser $user, Tracker_Artifact $execution_artifact, NodeRepresentation $execution_representation) {
        $definition_artifact = $this->getDefinitionArtifact($execution_artifact, $execution_representation);
        if ($definition_artifact) {
            return $this->artifact_builder->getNodeRepresentation($user, $definition_artifact);
        }
        return null;
    }

    private function getDefinitionArtifact(Tracker_Artifact $execution_artifact, NodeRepresentation $execution_representation) {
        $definition_tracker = $this->getDefinitionTrackerId($execution_artifact);
        foreach($execution_representation->links as $link) {
            $art = $this->artifact_factory->getArtifactById($link->id);
            if ($art->getTrackerId() == $definition_tracker) {
                return $art;
            }
        }
        return null;
    }

    private function getDefinitionTrackerId(Tracker_Artifact $execution_artifact) {
        $dar = $this->dao->searchByProjectId($execution_artifact->getTracker()->getGroupId());
        $row = $dar->getRow();
        return $row['test_definition_tracker_id'];
    }
}
