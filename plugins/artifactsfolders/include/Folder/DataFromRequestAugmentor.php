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

use Codendi_Request;
use Tracker_ArtifactFactory;
use Tuleap\ArtifactsFolders\Nature\NatureInFolderPresenter;

class DataFromRequestAugmentor
{
    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var HierarchyOfFolderBuilder
     */
    private $hierarchy_builder;

    public function __construct(
        Codendi_Request $request,
        HierarchyOfFolderBuilder $hierarchy_builder
    ) {
        $this->request           = $request;
        $this->hierarchy_builder = $hierarchy_builder;
    }

    public function augmentDataFromRequest(array &$fields_data)
    {
        $new_artifact_folder_id = (int) $this->request->get('new-artifact-folder');
        $previous_folder        = $this->getExistingFolderForArtifactInRequest();

        if (! $new_artifact_folder_id && ! $previous_folder) {
            return;
        } elseif (! $new_artifact_folder_id && $previous_folder) {
            $this->removeFolderFromFieldsData($fields_data, $previous_folder->getId());
        } elseif ($new_artifact_folder_id && ! $previous_folder) {
            $this->addFolderInFieldsData($fields_data, $new_artifact_folder_id);
        } elseif ($new_artifact_folder_id && (int) $previous_folder->getId() !== $new_artifact_folder_id) {
            $this->removeFolderFromFieldsData($fields_data, $previous_folder->getId());
            $this->addFolderInFieldsData($fields_data, $new_artifact_folder_id);
        }
    }

    private function getExistingFolderForArtifactInRequest()
    {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($this->request->get('aid'));
        if (! $artifact) {
            return null;
        }

        $folder_hierarchy = $this->hierarchy_builder->getHierarchyOfFolderForArtifact(
            $artifact
        );

        return end($folder_hierarchy);
    }

    private function removeFolderFromFieldsData(array &$fields_data, $folder_id)
    {
        $fields_data['removed_values'][$folder_id] = $folder_id;
    }

    private function addFolderInFieldsData(array &$fields_data, $new_artifact_folder_id)
    {
        if (strlen($fields_data['new_values'])) {
            $fields_data['new_values'] .= ',';
        }
        $fields_data['new_values'] .= $new_artifact_folder_id;
        $fields_data['natures'][$new_artifact_folder_id] = NatureInFolderPresenter::NATURE_IN_FOLDER;
    }
}
