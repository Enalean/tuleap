<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


class Tracker_FileInfoFactory {
    /**
     * @var Tracker_FileInfoDao
     */
    private $dao;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(Tracker_FileInfoDao $dao, Tracker_FormElementFactory $formelement_factory, Tracker_ArtifactFactory $artifact_factory) {
        $this->dao                 = $dao;
        $this->formelement_factory = $formelement_factory;
        $this->artifact_factory    = $artifact_factory;
    }

    /**
     *
     * @param type $id
     *
     * @return Tracker_FileInfo
     */
    public function getById($id) {
        $row = $this->dao->searchById($id)->getRow();
        if (! $row) {
            return;
        }

        $field_id = $this->dao->searchFieldIdByFileInfoId($id);
        if (! $field_id) {
            return;
        }

        $field = $this->formelement_factory->getFormElementById($field_id);
        if (! $field) {
            return;
        }

        if (! $field->isUsed()) {
            return;
        }

        return new Tracker_FileInfo(
            $row['id'],
            $field,
            $row['submitted_by'],
            $row['description'],
            $row['filename'],
            $row['filesize'],
            $row['filetype']
        );
    }

    /**
     *
     * @param type $id
     *
     * @return Tracker_Artifact
     * @throws Tracker_FileInfo_InvalidFileInfoException
     * @throws Tracker_FileInfo_UnauthorisedException
     */
    public function getArtifactByFileInfoId(PFUser $user, $id) {
        $row = $this->dao->searchArtifactIdByFileInfoId($id)->getRow();
        if (! $row) {
            throw new Tracker_FileInfo_InvalidFileInfoException('File does not exist');
        }

        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $row['artifact_id']);
        if ($artifact == null) {
            throw new Tracker_FileInfo_UnauthorisedException('User can\'t access the artifact the file is attached to');
        }
        return $artifact;
    }
}

?>