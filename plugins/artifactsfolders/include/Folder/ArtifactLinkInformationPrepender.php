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
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use PFUser;

class ArtifactLinkInformationPrepender
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Dao
     */
    private $folder_dao;

    /**
     * @var FolderForArtifactGoldenRetriever
     */
    private $retriever;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Dao $folder_dao,
        FolderForArtifactGoldenRetriever $retriever
    ) {
        $this->artifact_factory = $artifact_factory;
        $this->folder_dao       = $folder_dao;
        $this->retriever        = $retriever;
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
        $folder = $this->retriever->getFolder($artifact, $current_user);
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
        $purify           = Codendi_HTMLPurifier::instance();
        $artifact_factory = Tracker_ArtifactFactory::instance();
        $folder_dao       = new Dao();
        $project          = $artifact->getTracker()->getProject();

        $folders = array();
        foreach ($folder_dao->searchFoldersInProject($project->getId()) as $row) {
            $folder = $artifact_factory->getInstanceFromRow($row);
            if ($folder->userCanView($current_user)) {
                $selected = '';
                if ($current_folder && $current_folder->getId() === $folder->getId()) {
                    $selected = 'selected';
                }
                $option = '<option value="' . $folder->getId() . '" ' . $selected . '>';
                $option .= $purify->purify($folder->getXRef() . ' ' . $folder->getTitle());
                $option .= '</option>';

                $folders[] = $option;
            }
        }

        if (count($folders) === 0) {
            return;
        }

        return '<select name="new-artifact-folder"><option value=""></option>' . implode('', $folders) . '</select>';
    }
}
