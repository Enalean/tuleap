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

namespace Tuleap\ArtifactsFolders\Folder;

use PFUser;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\ArtifactsFolders\Nature\NatureInFolderPresenter;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;

class ArtifactPresenterBuilder
{
    /**
     * @var NatureDao
     */
    private $nature_dao;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var HierarchyOfFolderBuilder
     */
    private $hierarchy_builder;

    /**
     * @var NatureIsChildLinkRetriever
     */
    private $child_link_retriever;

    public function __construct(
        HierarchyOfFolderBuilder $hierarchy_builder,
        NatureDao $nature_dao,
        NatureIsChildLinkRetriever $child_link_retriever,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->nature_dao           = $nature_dao;
        $this->artifact_factory     = $artifact_factory;
        $this->hierarchy_builder    = $hierarchy_builder;
        $this->child_link_retriever = $child_link_retriever;
    }

    /** @return ArtifactPresenter[] */
    public function buildInFolder(PFUser $user, Artifact $folder)
    {
        $list_artifact_representation = $this->buildInFolderWithNoFilter($user, $folder);

        $linked_folders_ids = [];
        foreach ($list_artifact_representation as $artifact_representation) {
            $linked_folders_ids[] = $artifact_representation->id;
        }

        $children_ids = [];
        foreach ($linked_folders_ids as $artifact_id) {
            $children_ids = array_merge($children_ids, $this->collectChildrenIds($artifact_id, $linked_folders_ids));
        }

        $list_artifact_representation = array_filter(
            $list_artifact_representation,
            function ($artifact_representation) use ($children_ids) {
                return ! in_array($artifact_representation->id, $children_ids);
            }
        );

        return array_values($list_artifact_representation);
    }

    /**
     * @return ArtifactPresenter[]
     */
    private function buildInFolderWithNoFilter(PFUser $user, Artifact $folder)
    {
        $linked_artifacts_ids = $this->nature_dao->getReverseLinkedArtifactIds(
            $folder->getId(),
            NatureInFolderPresenter::NATURE_IN_FOLDER,
            PHP_INT_MAX,
            0
        );

        $folder_hierarchy = $this->hierarchy_builder->getHierarchyOfFolder($folder);

        $list_artifact_representation = $this->getListOfArtifactRepresentation($user, $linked_artifacts_ids, $folder_hierarchy);

        $children_folder = $this->child_link_retriever->getChildren($folder);
        foreach ($children_folder as $child_folder) {
            $list_artifact_representation = array_merge(
                $list_artifact_representation,
                $this->buildInFolderWithNoFilter($user, $child_folder)
            );
        }

        return $list_artifact_representation;
    }

    /**
     * @return array
     */
    private function collectChildrenIds($artifact_id, array $already_visited)
    {
        $children_ids_for_current_artifact = $this->nature_dao->getForwardLinkedArtifactIds(
            $artifact_id,
            Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD,
            PHP_INT_MAX,
            0
        );
        $already_visited[] = $artifact_id;
        $children_ids      = $children_ids_for_current_artifact;
        foreach ($children_ids_for_current_artifact as $child_id) {
            if (! in_array($child_id, $already_visited)) {
                $children_ids = array_merge($children_ids, $this->collectChildrenIds($child_id, $already_visited));
            }
        }

        return $children_ids;
    }

    /** @return ArtifactPresenter[] */
    public function buildIsChild(PFUser $user, Artifact $artifact)
    {
        $linked_artifacts_ids = $this->nature_dao->getForwardLinkedArtifactIds(
            $artifact->getId(),
            Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD,
            PHP_INT_MAX,
            0
        );

        return $this->getListOfChildrenRepresentation($user, $linked_artifacts_ids);
    }

    private function getListOfChildrenRepresentation(PFUser $user, $list_of_artifact_ids)
    {
        $artifact_representations = [];
        foreach ($list_of_artifact_ids as $artifact_id) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $artifact_id);
            if ($artifact) {
                $folder_hierarchy           = $this->hierarchy_builder->getHierarchyOfFolderForArtifact($artifact);
                $artifact_representations[] = $this->getArtifactRepresentation($user, $artifact, $folder_hierarchy);
            }
        }

        return $artifact_representations;
    }

    private function getListOfArtifactRepresentation(PFUser $user, $list_of_artifact_ids, array $folder_hierarchy)
    {
        $artifact_representations = [];
        foreach ($list_of_artifact_ids as $artifact_id) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $artifact_id);
            if ($artifact) {
                $artifact_representations[] = $this->getArtifactRepresentation($user, $artifact, $folder_hierarchy);
            }
        }

        return $artifact_representations;
    }

    private function getArtifactRepresentation(PFUser $user, Artifact $artifact, array $folder_hierarchy)
    {
        $artifact_representation = new ArtifactPresenter();
        $artifact_representation->build($user, $artifact, $folder_hierarchy);

        return $artifact_representation;
    }
}
