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

use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;

class HierarchyOfFolderBuilder
{
    /**
     * @var NatureIsChildLinkRetriever
     */
    private $retriever;

    /**
     * @var Dao
     */
    private $folder_dao;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        Dao $folder_dao,
        NatureIsChildLinkRetriever $retriever,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->retriever        = $retriever;
        $this->folder_dao       = $folder_dao;
        $this->artifact_factory = $artifact_factory;
    }

    public function getHierarchyOfFolderForArtifact(Tracker_Artifact $artifact)
    {
        $hierarchy = array();
        $row = $this->folder_dao->searchFoldersTheArtifactBelongsTo($artifact->getId())->getRow();
        if ($row) {
            $folder    = $this->artifact_factory->getInstanceFromRow($row);
            $hierarchy = $this->getHierarchyOfFolder($folder);
        }

        return $hierarchy;
    }

    public function getHierarchyOfFolder(Tracker_Artifact $folder)
    {
        $hierarchy = array();
        foreach ($this->retriever->getParentsHierarchy($folder)->getArtifacts() as $ancestors) {
            $parent_folder = $this->getFirstParentThatIsAFolder($ancestors);
            if (! $parent_folder) {
                break;
            }

            $hierarchy[] = $parent_folder;
        }
        array_unshift($hierarchy, $folder);

        return array_reverse($hierarchy);
    }

    /**
     * @return null|Tracker_Artifact
     */
    public function getDirectFolderForArtifact(Tracker_Artifact $artifact)
    {
        $row = $this->folder_dao->searchFoldersTheArtifactBelongsTo($artifact->getId())->getRow();
        if ($row) {
            return $this->artifact_factory->getInstanceFromRow($row);
        }

        return null;
    }

    private function getFirstParentThatIsAFolder($ancestors)
    {
        $parent_folder = null;
        foreach ($ancestors as $parent) {
            \assert($parent instanceof Tracker_Artifact);
            if ($this->folder_dao->isTrackerConfiguredToContainFolders($parent->getTrackerId())) {
                $parent_folder = $parent;
            }
        }

        return $parent_folder;
    }
}
