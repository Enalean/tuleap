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
use Project;
use Tracker_Artifact;
use Tracker_ArtifactFactory;

class FolderHierarchicalRepresentationCollectionBuilder
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Dao
     */
    private $folder_dao;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Dao $folder_dao
    ) {
        $this->artifact_factory = $artifact_factory;
        $this->folder_dao       = $folder_dao;
    }

    /** @return FolderHierarchicalRepresentationCollection */
    public function buildFolderHierarchicalRepresentationCollection(
        Tracker_Artifact $artifact,
        Project $project,
        PFUser $current_user
    ) {
        $all_folders = $this->getAllFoldersAsFolderHierarchicalRepresentationCollection(
            $artifact,
            $project,
            $current_user
        );

        return $this->convertAllFoldersToForestOfTopFolders($current_user, $all_folders);
    }

    /** @return FolderHierarchicalRepresentationCollection */
    private function convertAllFoldersToForestOfTopFolders(
        PFUser $current_user,
        FolderHierarchicalRepresentationCollection $all_folders
    ) {
        $top_level_folders = new FolderHierarchicalRepresentationCollection();
        foreach ($all_folders->toArray() as $folder_representation) {
            \assert($folder_representation instanceof FolderHierarchicalRepresentation);
            $parent = $this->getParent($all_folders, $folder_representation->getParentId(), $current_user);
            if ($parent && $all_folders->contains($parent)) {
                $all_folders->get($parent)->addChild($folder_representation);
            } else {
                $top_level_folders->add($folder_representation);
            }
        }

        return $top_level_folders;
    }

    /** @return FolderHierarchicalRepresentationCollection */
    private function getAllFoldersAsFolderHierarchicalRepresentationCollection(
        Tracker_Artifact $artifact,
        Project $project,
        PFUser $current_user
    ) {
        $all_folders = new FolderHierarchicalRepresentationCollection();
        foreach ($this->folder_dao->searchFoldersInProject($project->getId()) as $row) {
            $folder = $this->artifact_factory->getInstanceFromRow($row);
            if ($folder->getId() === $artifact->getId()) {
                continue;
            }
            $all_folders->add(new FolderHierarchicalRepresentation($folder, $row['parent_id']));
        }

        return $all_folders;
    }

    /** @return Tracker_Artifact */
    private function getParent(
        FolderHierarchicalRepresentationCollection $all_folders,
        $parent_id,
        PFUser $current_user
    ) {
        $parent_representation = $all_folders->getById($parent_id);
        if (! $parent_representation) {
            return null;
        }
        $parent = $parent_representation->getFolder();

        if (! $parent->userCanView($current_user)) {
            return null;
        }

        return $parent;
    }
}
