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
use PFUser;
use Tracker_Artifact;

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
        $read_only,
        array $additional_classes
    ) {
        if ($reverse_artifact_links) {
            return '';
        }

        $value            = false;
        $folder_hierarchy = $this->hierarchy_builder->getHierarchyOfFolderForArtifact($artifact);

        if ($folder_hierarchy) {
            $value = $this->fetchLinkToFolder($folder_hierarchy);
        }

        if (! $read_only) {
            $current_folder = null;
            if ($folder_hierarchy) {
                $current_folder = end($folder_hierarchy);
            }
            $value .= $this->fetchSelectBox($artifact, $current_user, $additional_classes, $current_folder);
        }

        return '<p>' . $this->fetchFolderLabel($folder_hierarchy)
            . ' ' . $value .
            '</p>';
    }

    private function fetchLinkToFolder(array $folder_hierarchy)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $folders = array();
        foreach ($folder_hierarchy as $folder) {
            \assert($folder instanceof Tracker_Artifact);
            $uri = $folder->getUri() . '&view=artifactsfolders';

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
        array $additional_classes,
        ?Tracker_Artifact $current_folder = null
    ) {
        $class = "";
        if (count($additional_classes) === 0) {
            $class = 'class=tracker-form-element-artifactlink-prepended';
        }

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

        $purifier = Codendi_HTMLPurifier::instance();
        return '<select name="new-artifact-folder" ' . $purifier->purify($class) . '>
            <option value="" class="not-anymore-in-folder">'
            . $GLOBALS['Language']->getText('plugin_folders', 'no_folder') . '</option>'
            . implode('', $options) . '</select>';
    }

    private function fetchFolderLabel(array $folder_hierarchy)
    {
        $class = "";
        if (empty($folder_hierarchy)) {
            $class = 'class=tracker-form-element-artifactlink-prepended';
        }

        $purifier = Codendi_HTMLPurifier::instance();
        return '<span ' . $purifier->purify($class) . '>' .
            $GLOBALS['Language']->getText('plugin_folders', 'current_folder') .
            '</span>';
    }
}
