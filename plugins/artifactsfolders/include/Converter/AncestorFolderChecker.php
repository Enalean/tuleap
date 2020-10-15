<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\ArtifactsFolders\Converter;

use Tuleap\ArtifactsFolders\Folder\HierarchyOfFolderBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;

class AncestorFolderChecker
{
    /**
     * @var NatureIsChildLinkRetriever
     */
    private $nature_is_child_retriever;
    /**
     * @var HierarchyOfFolderBuilder
     */
    private $hierarchy_of_folder_builder;

    public function __construct(
        NatureIsChildLinkRetriever $nature_is_child_retriever,
        HierarchyOfFolderBuilder $hierarchy_of_folder_builder
    ) {
        $this->nature_is_child_retriever   = $nature_is_child_retriever;
        $this->hierarchy_of_folder_builder = $hierarchy_of_folder_builder;
    }

    public function isAncestorInSameFolder(
        Artifact $folder_artifact,
        Artifact $item_artifact
    ) {
        $ancestors_of_artifact = $this->nature_is_child_retriever->getParentsHierarchy($item_artifact);
        foreach ($ancestors_of_artifact->getArtifacts() as $ancestors) {
            foreach ($ancestors as $ancestor_artifact) {
                $direct_ancestor_folder = $this->hierarchy_of_folder_builder->getDirectFolderForArtifact(
                    $ancestor_artifact
                );

                if ($direct_ancestor_folder) {
                    return $direct_ancestor_folder->getId() === $folder_artifact->getId();
                }
            }
        }

        return false;
    }
}
