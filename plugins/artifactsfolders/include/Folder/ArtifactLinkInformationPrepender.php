<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
use Tracker_Artifact;
use PFUser;

class ArtifactLinkInformationPrepender
{
    /**
     * @var HierarchyOfFolderBuilder
     */
    private $hierarchy_builder;
    /**
     * @var FolderHierarchicalRepresentationCollectionBuilder
     */
    private $builder;

    public function __construct(
        HierarchyOfFolderBuilder $hierarchy_builder,
        FolderHierarchicalRepresentationCollectionBuilder $builder
    ) {
        $this->hierarchy_builder = $hierarchy_builder;
        $this->builder           = $builder;
    }

    public function prependArtifactLinkInformation(
        Tracker_Artifact $artifact,
        PFUser $current_user,
        $reverse_artifact_links,
        $read_only
    ) {
        if ($reverse_artifact_links) {
            return '';
        }

        $value            = false;
        $folder_hierarchy = $this->hierarchy_builder->getHierarchyOfFolderForArtifact($artifact);

        if (empty($folder_hierarchy)) {
            return '';
        }
        if ($folder_hierarchy) {
            $value = '<div class="tracker_formelement_read_and_edit_read_section">' . $this->fetchLinkToFolder($folder_hierarchy) . '</div>';
        }
        if (! $read_only) {
            $current_folder = null;
            if ($folder_hierarchy) {
                $current_folder = end($folder_hierarchy);
            }
            $value .= '<div class="tracker_formelement_read_and_edit_edition_section">' .
                $this->fetchSelectBox($artifact, $current_user, $current_folder) . '</div>';
        }

        return '<p>' . $GLOBALS['Language']->getText('plugin_folders', 'current_folder') . ' ' . $value . '</p>';
    }

    private function fetchLinkToFolder(array $folder_hierarchy)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $folders = array();
        /** @var Tracker_Artifact $folder */
        foreach ($folder_hierarchy as $folder) {
            $uri = $folder->getUri().'&view=artifactsfolders';

            $link = '<a href="' . $purifier->purify($uri) . '" class="direct-link-to-artifact">';
            $link .= $purifier->purify($folder->getTitle());
            $link .= '</a>';

            $folders[] = $link;
        }

        return implode(' <i class="fa fa-angle-right"></i> ', $folders);
    }

    private function fetchSelectBox(
        Tracker_Artifact $artifact,
        PFUser $current_user,
        Tracker_Artifact $current_folder = null
    ) {
        $project = $artifact->getTracker()->getProject();

        $options    = array();
        $collection = $this->builder->buildFolderHierarchicalRepresentationCollection(
            $artifact,
            $project,
            $current_user
        );
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
