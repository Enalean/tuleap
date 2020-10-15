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
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand;
use Tuleap\ArtifactsFolders\Nature\NatureInFolderPresenter;
use Tuleap\Tracker\Artifact\Artifact;

class PostSaveNewChangesetCommand implements Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand
{
    /**
     * @var Tracker_FormElement_Field
     */
    private $field;

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var Dao
     */
    private $folder_dao;

    public function __construct(Tracker_FormElement_Field $field, Codendi_Request $request, Dao $folder_dao)
    {
        $this->field      = $field;
        $this->request    = $request;
        $this->folder_dao = $folder_dao;
    }

    public function execute(
        Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        ?Tracker_Artifact_Changeset $previous_changeset = null
    ) {
        if (! $this->request->exist('new-artifact-folder')) {
            return;
        }

        $new_artifact_folder_id = intval($this->request->get('new-artifact-folder'));
        if ($new_artifact_folder_id) {
            $this->folder_dao->addInFolderNature(
                $new_changeset->getId(),
                $this->field->getId(),
                $new_artifact_folder_id,
                NatureInFolderPresenter::NATURE_IN_FOLDER
            );
        } else {
            $this->folder_dao->removeInFolderLink(
                $new_changeset->getId(),
                $this->field->getId(),
                $new_artifact_folder_id
            );
        }
    }
}
