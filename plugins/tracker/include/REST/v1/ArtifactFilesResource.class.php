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

use \Tuleap\REST\ProjectAuthorization;
use \Luracast\Restler\RestException;
use \Tracker_Artifact_Attachment_TemporaryFile                    as TemporaryFile;
use \Tracker_Artifact_Attachment_TemporaryFileManager             as FileManager;
use \Tracker_Artifact_Attachment_TemporaryFileManagerDao          as FileManagerDao;
use \Tuleap\Tracker\REST\Artifact\FileInfoRepresentation          as FileInfoRepresentation;
use \Tracker_Artifact_Attachment_CannotCreateException            as CannotCreateException;
use \Tracker_Artifact_Attachment_TemporaryFileTooBigException     as TemporaryFileTooBigException;
use \Tracker_Artifact_Attachment_ChunkTooBigException             as ChunkTooBigException;
use \Tracker_Artifact_Attachment_InvalidPathException             as InvalidPathException;
use \Tracker_Artifact_Attachment_MaxFilesException                as MaxFilesException;
use \Tracker_Artifact_Attachment_FileNotFoundException            as FileNotFoundException;
use \Tracker_Artifact_Attachment_InvalidOffsetException           as InvalidOffsetException;
use \Tracker_FileInfo_InvalidFileInfoException                    as InvalidFileInfoException;
use \Tracker_FileInfo_UnauthorisedException                       as UnauthorisedException;
use \Tuleap\Tracker\REST\Artifact\FileDataRepresentation          as FileDataRepresentation;
use \Tracker_Artifact_Attachment_PermissionDeniedOnFieldException as PermissionDeniedOnFieldException;
use \Tuleap\REST\Exceptions\LimitOutOfBoundsException;
use \Tuleap\REST\Header;
use \UserManager;
use \PFUser;
use \Tracker_ArtifactFactory;
use \Tracker_FormElementFactory;
use \Tracker_FileInfoFactory;
use \Tracker_FileInfoDao;
use \Tracker_REST_Artifact_ArtifactUpdater;
use \Tracker_REST_Artifact_ArtifactValidator;
use \Tracker_URLVerification;

class ArtifactFilesResource {

    const STATUS_TEMPORARY = 'temporary';

    const DEFAULT_LIMIT = 1048576; // 1Mo

    public static $valid_status = array(
        self::STATUS_TEMPORARY
    );

    /** @var PFUser */
    private $user;

    /** @var Tracker_Artifact_Attachment_TemporaryFileManager */
    private $file_manager;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker_FileInfoFactory */
    private $fileinfo_factory;


    public function __construct() {
        $this->user                = UserManager::instance()->getCurrentUser();
        $this->artifact_factory    = Tracker_ArtifactFactory::instance();
        $this->formelement_factory = Tracker_FormElementFactory::instance();
        $this->fileinfo_factory    = new Tracker_FileInfoFactory(
            new Tracker_FileInfoDao(),
            $this->formelement_factory,
            $this->artifact_factory
        );
        $this->file_manager = new FileManager(
            $this->user,
            new FileManagerDao(),
            $this->fileinfo_factory
        );
    }

    /**
     * Get a chunk of given file
     *
     * A user can only access its own temporary files or attached files if he has the rights to see them.
     *
     * @url GET {id}
     * @param int $id     Id of the file
     * @param int $offset Where to start to read the file
     * @param int $limit  How much to read the file
     *
     * @return \Tuleap\Tracker\REST\Artifact\FileDataRepresentation
     *
     * @throws 401
     * @throws 403
     * @throws 404
     * @throws 406
     */
    protected function getId($id, $offset = 0, $limit = self::DEFAULT_LIMIT) {
        $this->checkLimitValue($limit);

        $chunk = '';
        $size  = 0;

        if ($this->file_manager->isFileIdTemporary($id)) {
            $file  = $this->getFile($id, $this->user);

            $chunk = $this->getTemporaryFileContent($file, $offset, $limit);
            $size  = $file->getSize();
        } else {
            $chunk = $this->getAttachedFileContent($id, $offset, $limit);
            $size  = $this->getAttachedFileSize($id);
        }

        $this->sendAllowHeadersForArtifactFileId();
        $this->sendPaginationHeaders($limit, $offset, $size);

        $file_data_representation = new FileDataRepresentation();
        return $file_data_representation->build($chunk);
    }

    /**
     * Get files representation
     *
     * For now, only user's temporary files can be retrieved
     *
     * @url GET
     *
     * @param string $status Accepted values: [temporary]
     *
     * @return Array {@type \Tuleap\Tracker\REST\Artifact\FileInfoRepresentation}
     *
     * @throws 400
     */
    protected function get($status) {
        $this->validateStatus($status);

        if ($status == self::STATUS_TEMPORARY) {
            $files                 = $this->file_manager->getUserTemporaryFiles();
            $files_representations = array();

            foreach ($files as $file) {
                $files_representations[] = $this->buildFileRepresentation($file);
            }

            return $files_representations;
        }
    }

    /**
     * @param type $status
     * @throws 400
     */
    private function validateStatus($status) {
        if (! in_array($status, self::$valid_status)) {
            throw new RestException(400, "Invalid value for parameter 'status'");
        }
    }

    /**
     * @throws 404
     */
    private function getTemporaryFileContent($file, $offset, $limit) {
        try {
            return $this->file_manager->getTemporaryFileChunk($file, $offset, $limit);

        } catch (FileNotFoundException $e) {
            throw new RestException(404);
        }
    }

    /**
     * @throws 403
     * @throws 404
     */
    private function getAttachedFileContent($id, $offset, $limit) {
        try {
            return $this->file_manager->getAttachedFileChunk($id, $this->user, $offset, $limit);

        } catch (PermissionDeniedOnFieldException $e) {
            throw new RestException(403);

        } catch (FileNotFoundException $e) {
            throw new RestException(404);
        }
    }

    /**
     * @throws 404
     */
    private function getAttachedFileSize($id) {
        try {
            return $this->file_manager->getAttachedFileSize($id);

        } catch (FileNotFoundException $e) {
            throw new RestException(404);
        }
    }

    private function checkLimitValue($limit) {
        if ($limit > FileManager::getMaximumChunkSize()) {
            throw new LimitOutOfBoundsException(FileManager::getMaximumChunkSize());
        }
    }

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
        $this->sendAllowHeadersForArtifactFile();

        try {
            $this->file_manager->validateChunkSize($content);

            $file         = $this->file_manager->save($name, $description, $mimetype);
            $chunk_offset = 1;
            $append       = $this->file_manager->appendChunk($content, $file, $chunk_offset);

        } catch (CannotCreateException $e) {
            throw new RestException(500);
        } catch (ChunkTooBigException $e) {
            throw new RestException(406, 'Uploaded content exceeds maximum size of ' . FileManager::getMaximumChunkSize());
        } catch (TemporaryFileTooBigException $e) {
            throw new RestException(406, "Temporary file's content exceeds maximum size of " . FileManager::getMaximumTemporaryFileSize());
        } catch (InvalidPathException $e) {
            throw new RestException(500, $e->getMessage());
        } catch (MaxFilesException $e) {
            throw new RestException(403, 'Maximum number of temporary files reached: ' . FileManager::TEMP_FILE_NB_MAX);
        }

        if (! $append) {
            throw new RestException(500);
        }

        return $this->buildFileRepresentation($file);
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
     *
     * @return \Tuleap\Tracker\REST\Artifact\FileInfoRepresentation
     * @throws 406
     */
    protected function patchId($id, $content, $offset) {
        $this->sendAllowHeadersForArtifactFileId();

        if (! $this->file_manager->isFileIdTemporary($id)) {
            throw new RestException(404, 'File is not modifiable');
        }

        $file = $this->getFile($id);

        try {
            $this->file_manager->validateChunkSize($content);
            $this->file_manager->validateTemporaryFileSize($file, $content);

            $this->file_manager->appendChunk($content, $file, $offset);

        } catch (ChunkTooBigException $e) {
            throw new RestException(406, 'Uploaded content exceeds maximum size of ' . FileManager::getMaximumChunkSize());
        } catch (TemporaryFileTooBigException $e) {
            throw new RestException(406, "Temporary file's content exceeds maximum size of " . FileManager::getMaximumTemporaryFileSize());
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
    protected function optionsId($id) {
        $this->sendAllowHeadersForArtifactFileId();

        $this->getFile($id);
    }

    /**
     *
     * @param TemporaryFile $file
     * @return FileInfoRepresentation
     */
    private function buildFileRepresentation(TemporaryFile $file) {
        $reference = new FileInfoRepresentation();
        return $reference->build(
            $file->getId(),
            $file->getCreatorId(),
            $file->getDescription(),
            $file->getName(),
            $file->getSize(),
            $file->getType()
        );
    }

    /**
     * @param int $id
     * @return TemporaryFile
     * @throws RestException
     */
    private function getFile($id) {
        try {
            $file = $this->file_manager->getFile($id);

        } catch (FileNotFoundException $e) {
            throw new RestException(404);
        }

        $this->checkTemporaryFileBelongsToCurrentUser($file);

        return $file;
    }

    private function checkTemporaryFileBelongsToCurrentUser(TemporaryFile $file) {
        $creator_id = $file->getCreatorId();

        if ($creator_id != $this->user->getId()) {
            throw new RestException(401, 'This file does not belong to you');
        }
    }

    /**
     * Delete a temporary file or a file attached to an artifact
     *
     * @url DELETE {id}
     *
     * @throws 500, 400
     *
     * @param string $id Id of the file
     */
    protected function delete($id) {
        Header::allowOptionsDelete();
        try {
            if (! $this->isFileTemporary($id)) {
                $this->removeAttachedFile($id);
            } else {
                $this->removeTemporaryFile($id);
            }
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (InvalidFileInfoException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }
    }

    private function sendAllowHeadersForArtifactFile() {
        Header::allowOptionsPost();
        Header::sendMaxFileChunkSizeHeaders(FileManager::getMaximumChunkSize());
    }


    private function sendAllowHeadersForArtifactFileId() {
        Header::allowOptionsGetPatch();
        Header::sendMaxFileChunkSizeHeaders(FileManager::getMaximumChunkSize());
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, FileManager::getMaximumChunkSize());
    }

    /**
     * @param int $id
     *
     * @return Tracker_Artifact
     */
    private function getArtifactByFileInfoId($fileinfo_id) {
        try {
            $artifact = $this->fileinfo_factory->getArtifactByFileInfoId($this->user, $fileinfo_id);
        } catch (InvalidFileInfoException $e) {
            throw new RestException(404, $e->getMessage());
        } catch (UnauthorisedException $e) {
            throw new RestException(403, $e->getMessage());
        }

        if ($artifact) {
            ProjectAuthorization::userCanAccessProject($this->user, $artifact->getTracker()->getProject(), new Tracker_URLVerification());
            return $artifact;
        }
    }

    private function isFileTemporary($id) {
        return $this->file_manager->isFileIdTemporary($id);
    }

    private function removeAttachedFile($id) {
        $artifact = $this->getArtifactByFileInfoId($id);
        $values   = $this->fileinfo_factory->getValuesForDeletionByFileInfoId($id);

        $updater = new Tracker_REST_Artifact_ArtifactUpdater(
            new Tracker_REST_Artifact_ArtifactValidator(
                $this->formelement_factory
            )
        );
        $updater->update($this->user, $artifact, $values);
    }

    private function removeTemporaryFile($id) {
        $file = $this->getFile($id, $this->user);
        $this->file_manager->removeTemporaryFile($file);
    }
}
