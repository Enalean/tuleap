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

use Codendi_HTMLPurifier;
use Project;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use PFUser;
use Tracker_Hierarchy_Dao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;

class ArtifactLinkInformationPrepender
{
    /**
     * @var FolderForArtifactGoldenRetriever
     */
    private $folder_retriever;
    /**
     * @var FolderHierarchicalRepresentationCollectionBuilder
     */
    private $builder;

    public function __construct(
        FolderForArtifactGoldenRetriever $folder_retriever,
        FolderHierarchicalRepresentationCollectionBuilder $builder
    ) {
        $this->folder_retriever = $folder_retriever;
        $this->builder          = $builder;
    }

    public function prependArtifactLinkInformation(
        Tracker_Artifact $artifact,
        PFUser $current_user,
        $reverse_artifact_links,
        $read_only
    ) {
        if ($reverse_artifact_links) {
            return;
        }

        $value  = false;
        $folder = $this->folder_retriever->getFolder($artifact, $current_user);
        if ($read_only) {
            if ($folder) {
                $value = $folder->fetchDirectLinkToArtifactWithTitle();
            }
        } else {
            $value = $this->fetchSelectBox($artifact, $current_user, $folder);
        }

        if (! $value) {
            return;
        }

        $current_folder = $GLOBALS['Language']->getText('plugin_folders', 'current_folder');

        return '<p>' . $current_folder . ' ' . $value . '</p>';
    }

    private function fetchSelectBox(
        Tracker_Artifact $artifact,
        PFUser $current_user,
        Tracker_Artifact $current_folder = null
    ) {
        $project = $artifact->getTracker()->getProject();

        $options    = array();
        $collection = $this->builder->buildFolderHierarchicalRepresentationCollection($artifact, $project, $current_user);
        $collection->collectOptions($options, $current_folder);

        if (count($options) === 0) {
            return;
        }

        return '<select name="new-artifact-folder">
            <option value="" class="not-anymore-in-folder">'
            . $GLOBALS['Language']->getText('plugin_folders', 'no_folder') . '</option>'
            . implode('', $options) . '</select>';
    }
}
