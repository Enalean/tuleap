<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use \Luracast\Restler\RestException;
use \Tracker_Artifact_Attachment_TemporaryFile           as TemporaryFile;
use \Tracker_Artifact_Attachment_TemporaryFileManager    as FileManager;
use \Tracker_Artifact_Attachment_TemporaryFileManagerDao as FileManagerDao;
use \Tuleap\Tracker\REST\Artifact\FileInfoRepresentation as FileInfoRepresentation;
use \Tracker_Artifact_Attachment_CannotCreateException   as CannotCreateException;
use \Tracker_Artifact_Attachment_FileTooBigException     as FileTooBigException;
use \Tracker_Artifact_Attachment_InvalidPathException    as InvalidPathException;
use \Tracker_Artifact_Attachment_MaxFilesException       as MaxFilesException;
use \Tracker_Artifact_Attachment_FileNotFoundException   as FileNotFoundException;
use \Tracker_Artifact_Attachment_InvalidOffsetException  as InvalidOffsetException;
use \Tuleap\REST\Header;
use \UserManager;
use \PFUser;


class ArtifactFilesResource {

    /**
     * Create a temporary file
     *
     * Call this method to create a new file. To add new chunks, use PATCH on artifact_files/:ID
     *
     * @url POST
     * @param string $name          Name of the file {@from body}
     * @param string $description   Description of the file {@from body}
     * @param string $mimetype      Mime-Type of the file {@from body}
     * @param string $content       First chunk of the file (base64-encoded) {@from body}
     *
     * @return \Tuleap\Tracker\REST\Artifact\FileInfoRepresentation
     * @throws 500 406 403
     */
    protected function post($name, $description, $mimetype, $content) {
        $user         = UserManager::instance()->getCurrentUser();
        $file_manager = $this->getFileManager($user);

        $this->sendAllowHeadersForArtifactFile();

        try {
            $file         = $file_manager->save($name, $description, $mimetype);
            $chunk_offset = 1;
            $append       = $file_manager->appendChunkForREST($content, $file, $chunk_offset);
        } catch (CannotCreateException $e) {
            throw new RestException(500);
        } catch (FileTooBigException $e) {
            throw new RestException(406, 'Uploaded content exceeds maximum size of ' . FileManager::getMaximumFileChunkSize());
        } catch (InvalidPathException $e) {
            throw new RestException(500, $e->getMessage());
        } catch (MaxFilesException $e) {
            throw new RestException(403, 'Maximum number of temporary files reached: '. FileManager::TEMP_FILE_NB_MAX);
        }

        if (! $append) {
            throw new RestException(500);
        }

        return $this->buildFileRepresentation($file);
    }

    /**
     *
     * @param TemporaryFile $file
     * @return FileInfoRepresentation
     */
    private function buildFileRepresentation(TemporaryFile $file) {
        $reference = new FileInfoRepresentation();
        return $reference->build($file->getId(), $file->getCreatorId(), $file->getDescription(), $file->getName(), $file->getSize(), $file->getType());
    }

    /**
     * Append a chunk to a temporary file (not attached to any artifact)
     *
     * Use this method to append a chunk of file to any existing file created via POST on /artifact_files
     * <ol>
     *  <li>This method cannot be called on a file that is already referenced by an artifact
     *  </li>
     *  <li>The offset property is used by the server in order to detect error in the consistency of the data
     *      uploaded but it is not possible to upload chunks in the wrong order
     *  </li>
     *  <li>Only the user who created the temporary artifact_file can modify and view that file until it is attached to an artifact
     *  </li>
     * </ol>
     *
     * @url PATCH {id}
     *
     * @param int    $id      The ID of the temporary artifact_file
     * @param string $content Chunk of the file (base64-encoded) {@from body}
     * @param int    $offset  Used to check that the chunk uploaded is the next one (minimum value is 2) {@from body}
     */
    protected function patchId($id, $content, $offset) {
        $this->sendAllowHeadersForArtifactFileId();

        $user         = UserManager::instance()->getCurrentUser();
        $file_manager = $this->getFileManager($user);

        if (! $file_manager->isFileIdTemporary($id)) {
            throw new RestException(404, 'File is not modifiable');
        }

        $file = $this->getFile($id, $user);

        try {
            $file_manager->appendChunkForREST($content, $file, $offset);
        } catch (InvalidOffsetException $e) {
            throw new RestException(406, 'Invalid offset received. Expected: '. ($file->getCurrentChunkOffset() +1));
        }

        return $this->buildFileRepresentation($file);
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        $this->sendAllowHeadersForArtifactFile();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId($id) {
        $this->sendAllowHeadersForArtifactFileId();

        $user = UserManager::instance()->getCurrentUser();
        $this->getFile($id, $user);
    }

    /**
     *
     * @param int $id
     * @param FileManager $file_manager
     * @return TemporaryFile
     * @throws RestException
     */
    private function getFile($id, PFUser $user) {
        $file_manager = $this->getFileManager($user);

        try {
            $file = $file_manager->getFile($id);
        } catch (FileNotFoundException $e) {
            throw new RestException(404);
        }

        $this->checkFileBelongsToUser($file, $user);

        return $file;
    }

    private function checkFileBelongsToUser(TemporaryFile $file, PFUser $user) {
        $creator_id = $file->getCreatorId();
        if ($creator_id != $user->getId()) {
            throw new RestException(401, 'This file does not belong to you');
        }
    }

    /**
     * @param PFUser $user
     * @return FileManager
     */
    private function getFileManager(PFUser $user) {
        return new FileManager(
            $user,
            new FileManagerDao()
        );
    }

    private function sendAllowHeadersForArtifactFile() {
        Header::allowOptionsPost();
        Header::sendMaxFileChunkSizeHeaders(FileManager::getMaximumFileChunkSize());
    }

    private function sendAllowHeadersForArtifactFileId() {
        Header::allowOptionsPatch();
        Header::sendMaxFileChunkSizeHeaders(FileManager::getMaximumFileChunkSize());
    }
}
