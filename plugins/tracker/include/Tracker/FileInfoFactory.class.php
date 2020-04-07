<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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


class Tracker_FileInfoFactory
{
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

    public function __construct(Tracker_FileInfoDao $dao, Tracker_FormElementFactory $formelement_factory, Tracker_ArtifactFactory $artifact_factory)
    {
        $this->dao                 = $dao;
        $this->formelement_factory = $formelement_factory;
        $this->artifact_factory    = $artifact_factory;
    }

    public function getById(int $id): ?Tracker_FileInfo
    {
        static $cache = array();

        if (isset($cache[$id])) {
            return $cache[$id];
        }

        $row = $this->dao->searchById($id)->getRow();
        if (! $row) {
            return null;
        }

        $field_id = $this->dao->searchFieldIdByFileInfoId($id);
        if (! $field_id) {
            return null;
        }

        $field = $this->formelement_factory->getFormElementById($field_id);
        if (! $field) {
            return null;
        }

        if (! $field->isUsed()) {
            return null;
        }

        $file_info = new Tracker_FileInfo(
            $row['id'],
            $field,
            $row['submitted_by'],
            $row['description'],
            $row['filename'],
            $row['filesize'],
            $row['filetype']
        );

        $cache[$file_info->getId()] = $file_info;

        return $file_info;
    }

    /**
     * @throws Tracker_FileInfo_InvalidFileInfoException
     * @throws Tracker_FileInfo_UnauthorisedException
     */
    public function getArtifactByFileInfoIdAndUser(PFUser $user, int $id): Tracker_Artifact
    {
        $row = $this->dao->searchArtifactIdByFileInfoIdInLastChangeset($id)->getRow();
        if (! $row) {
            throw new Tracker_FileInfo_InvalidFileInfoException('File does not exist');
        }

        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $row['artifact_id']);
        if ($artifact === null) {
            throw new Tracker_FileInfo_UnauthorisedException('User can\'t access the artifact the file is attached to');
        }
        return $artifact;
    }

    /**
     * @return Tracker_Artifact|null
     */
    public function getArtifactByFileInfoIdInLastChangeset(int $id)
    {
        $row = $this->dao->searchArtifactIdByFileInfoIdInLastChangeset($id)->getRow();
        if (! $row) {
            return null;
        }

        return $this->artifact_factory->getArtifactById($row['artifact_id']);
    }

    /**
     *
     * @param int $id
     *
     * @return Tracker_Artifact | null
     */
    public function getArtifactByFileInfoId($id)
    {
        static $cache = array();
        if (! isset($cache[$id])) {
            $row = $this->dao->searchArtifactIdByFileInfoId($id)->getRow();
            if (! $row) {
                return;
            }

            $cache[$id] = $row['artifact_id'];
        }

        return $this->artifact_factory->getArtifactById($cache[$id]);
    }

    public function buildFileInfoData(Tracker_Artifact_Attachment_TemporaryFile $file, $path)
    {
        return array(
            'id'           => $file->getTemporaryName(),
            'submitted_by' => $file->getCreatorId(),
            'description'  => $file->getDescription(),
            'name'         => $file->getName(),
            'tmp_name'     => $path,
            'size'         => $file->getSize(),
            'type'         => $file->getType(),
            'error'        => UPLOAD_ERR_OK,
            'action'       => ''
        );
    }
}
