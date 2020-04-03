<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Tracker_Artifact;
use PFUser;
use Tracker_ArtifactDao;
use Tracker_ResourceDoesntExistException;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class ArtifactNodeBuilder
{

    /**
     * @var NodeBuilderFactory
     */
    private $node_builder_factory;

    /**
     * @var ArtifactNodeDao
     */
    private $dao;

    /**
     * @var Tracker_ArtifactDao
     */
    private $artifact_dao;

    public function __construct(Tracker_ArtifactDao $artifact_dao, ArtifactNodeDao $dao, NodeBuilderFactory $node_builder_factory)
    {
        $this->artifact_dao                   = $artifact_dao;
        $this->dao                            = $dao;
        $this->node_builder_factory           = $node_builder_factory;
    }

    public function getNodeRepresentation(PFUser $user, Tracker_Artifact $artifact): NodeRepresentation
    {
        $nodes        = array();
        $artifact_ids = array($artifact->getId());

        $node = new NodeRepresentation();
        $this->buildNode($node, $artifact->getId(), $nodes);

        $node->links         = $this->getLinks($user, $artifact->getId(), $nodes, $artifact_ids);
        $node->reverse_links = $this->getReverseLinks($user, $artifact->getId(), $nodes, $artifact_ids);

        $this->updateNodesWithArtifactValues($artifact_ids, $nodes);

        return $node;
    }

    /**
     * @return array
     *
     * @psalm-return list<mixed>
     */
    private function getLinks(PFUser $user, int $id, array &$nodes, array &$artifact_ids): array
    {
        $links = array();
        $this->appendNodeReferenceRepresentations(
            $links,
            $this->artifact_dao->getLinkedArtifacts($id),
            $user,
            $id,
            $nodes,
            $artifact_ids
        );
        $this->appendNodeReferenceRepresentations(
            $links,
            $this->dao->getCrossReferencesFromArtifact($id),
            $user,
            $id,
            $nodes,
            $artifact_ids
        );
        return array_values($links);
    }

    /**
     * @return array
     *
     * @psalm-return list<mixed>
     */
    private function getReverseLinks(PFUser $user, int $id, array &$nodes, array &$artifact_ids): array
    {
        $links = array();
        $this->appendNodeReferenceRepresentations(
            $links,
            $this->dao->getReverseLinkedArtifacts($id),
            $user,
            $id,
            $nodes,
            $artifact_ids
        );
        $this->appendNodeReferenceRepresentations(
            $links,
            $this->dao->getReverseCrossReferencesFromArtifact($id),
            $user,
            $id,
            $nodes,
            $artifact_ids
        );
        return array_values($links);
    }

    private function appendNodeReferenceRepresentations(array &$links, LegacyDataAccessResultInterface $dar, PFUser $user, int $id, array &$nodes, array &$artifact_ids): void
    {
        foreach ($this->getArtifactIdsUserCanSee($user, $dar, $links) as $id) {
            $link = new NodeReferenceRepresentation();
            $this->buildNode($link, $id, $nodes);
            $links[$id]     = $link;
            $artifact_ids[] = $id;
        }
    }

    /**
     * @return array
     *
     * @psalm-return list<mixed>
     */
    private function getArtifactIdsUserCanSee(PFUser $user, LegacyDataAccessResultInterface $dar, array $already_linked_ids): array
    {
        $artifact_ids = array();
        foreach ($dar as $row) {
            try {
                if ($this->notAlreadyLinked($already_linked_ids, $row['id'])) {
                    $this->node_builder_factory->getArtifactById($user, $row['id']);
                    $artifact_ids[] = $row['id'];
                }
            } catch (Tracker_ResourceDoesntExistException $exception) {
                // user cannot see, just skip
            }
        }
        return $artifact_ids;
    }

    private function notAlreadyLinked(array $already_linked_ids, int $id): bool
    {
        return ! isset($already_linked_ids[$id]);
    }

    private function buildNode(NodeReferenceRepresentation $node, int $id, array &$nodes): NodeReferenceRepresentation
    {
        $nodes[$id][] = $node;
        return $node;
    }

    /**
     * @param array $artifact_ids
     * @param NodeRepresentation[][] $nodes
     */
    private function updateNodesWithArtifactValues(array &$artifact_ids, array $nodes): void
    {
        $dar = $this->dao->getTitlesStatusAndTypes($artifact_ids);
        foreach ($dar as $row) {
            foreach ($nodes[$row['id']] as $node) {
                $node->build(
                    $row['id'],
                    NodeReferenceRepresentation::NATURE_ARTIFACT,
                    TRACKER_BASE_URL . '/?aid=' . $row['id'],
                    $row['item_name'],
                    $row['tracker_label'],
                    $row['color'],
                    (string) $row['title'],
                    $row['status_semantic'],
                    $row['status_label']
                );
            }
        }
    }
}
