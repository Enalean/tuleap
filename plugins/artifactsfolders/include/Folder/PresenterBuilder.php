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
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\ArtifactsFolders\Nature\NatureIsFolderPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;

class PresenterBuilder
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
     * @var Dao
     */
    private $folder_dao;

    public function __construct(
        Dao $folder_dao,
        NatureDao $nature_dao,
        Tracker_ArtifactFactory $artifact_factory,
        NatureIsChildLinkRetriever $retriever
    ) {
        $this->nature_dao       = $nature_dao;
        $this->artifact_factory = $artifact_factory;
        $this->retriever        = $retriever;
        $this->folder_dao       = $folder_dao;
    }

    /** @return Presenter */
    public function build(PFUser $user, Tracker_Artifact $folder)
    {
        $linked_artifacts_ids = $this->nature_dao->getReverseLinkedArtifactIds(
            $folder->getId(),
            NatureIsFolderPresenter::NATURE_IN_FOLDER,
            PHP_INT_MAX,
            0
        );

        $artifact_representations = array();
        foreach ($linked_artifacts_ids as $artifact_id) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $artifact_id);
            if ($artifact) {
                $artifact_representations[] = $this->getArtifactRepresentation($user, $artifact);
            }
        }

        return new Presenter(
            $artifact_representations,
            $this->getHierarchyOfFolder($folder)
        );
    }

    private function getHierarchyOfFolder(Tracker_Artifact $folder)
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

    private function getFirstParentThatIsAFolder($ancestors)
    {
        $parent_folder = null;
        foreach ($ancestors as $parent) {
            if ($this->folder_dao->isTrackerConfiguredToContainFolders($parent->getTrackerId())) {
                $parent_folder = $parent;
            }
        }

        return $parent_folder;
    }

    private function getArtifactRepresentation(PFUser $user, Tracker_Artifact $artifact)
    {
        $artifact_representation = new ArtifactPresenter();
        $artifact_representation->build($user, $artifact);

        return $artifact_representation;
    }
}
