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
     * @var Dao
     */
    private $folder_dao;

    /**
     * @var ArtifactPresenterBuilder
     */
    private $artifact_presenter_builder;

    public function __construct(
        Dao $folder_dao,
        ArtifactPresenterBuilder $artifact_presenter_builder,
        NatureIsChildLinkRetriever $retriever
    ) {
        $this->retriever                  = $retriever;
        $this->folder_dao                 = $folder_dao;
        $this->artifact_presenter_builder = $artifact_presenter_builder;
    }

    /** @return Presenter */
    public function build(PFUser $user, Tracker_Artifact $folder)
    {
        return new Presenter(
            $this->artifact_presenter_builder->buildInFolder($user, $folder),
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
}
