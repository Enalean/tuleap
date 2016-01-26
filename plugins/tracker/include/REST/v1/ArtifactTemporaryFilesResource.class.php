<?php
/**
 * Copyright (c) Enalean, 2014 — 2016. All Rights Reserved.
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

use Tuleap\REST\ProjectAuthorization;
use Luracast\Restler\RestException;
use Tracker_Artifact_Attachment_TemporaryFile                    as TemporaryFile;
use Tracker_Artifact_Attachment_TemporaryFileManager             as FileManager;
use Tracker_Artifact_Attachment_TemporaryFileManagerDao          as FileManagerDao;
use Tuleap\Tracker\REST\Artifact\FileInfoRepresentation          as FileInfoRepresentation;
use Tuleap\Tracker\REST\Artifact\FileDataRepresentation          as FileDataRepresentation;
use Tracker_Artifact_Attachment_CannotCreateException            as CannotCreateException;
use Tracker_Artifact_Attachment_ChunkTooBigException             as ChunkTooBigException;
use Tracker_Artifact_Attachment_InvalidPathException             as InvalidPathException;
use Tracker_Artifact_Attachment_FileNotFoundException            as FileNotFoundException;
use Tracker_Artifact_Attachment_InvalidOffsetException           as InvalidOffsetException;
use Tracker_FileInfo_InvalidFileInfoException                    as InvalidFileInfoException;
use Tracker_FileInfo_UnauthorisedException                       as UnauthorisedException;
use Tuleap\Tracker\Artifact\Attachment\QuotaExceededException;
use Tuleap\REST\Exceptions\LimitOutOfBoundsException;
use Tuleap\REST\Header;
use UserManager;
use PFUser;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_FileInfoFactory;
use Tracker_FileInfoDao;
use Tracker_URLVerification;
use System_Command;

class ArtifactTemporaryFilesResource {

    const DEFAULT_LIMIT = 1048576; // 1Mo

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
            $this->fileinfo_factory,
            new System_Command()
        );
    }

    /**
     * Get files representation
     *
     * For now, only temporary files created by the user can be retrieved
     *
     * @url GET
     *
     * @return Array {@type \Tuleap\Tracker\REST\Artifact\FileInfoRepresentation}
     *
     * @throws 400
     */
    protected function get() {
        $files                 = $this->file_manager->getUserTemporaryFiles();
        $files_representations = array();

        foreach ($files as $file) {
            $files_representations[] = $this->buildFileRepresentation($file);
        }

        $this->sendAllowHeadersForArtifactFiles();
        return $files_representations;
    }

    /**
     * Get a chunk of a file
     *
     * A user can only access their own temporary files
     *
     * @url GET {id}
     * @param int $id     Id of the file
     * @param int $offset Where to start to read the file
     * @param int $limit  How much to read the file
     *
     * @return \Tuleap\Tracker\REST\Artifact\FileDataRepresentation
     *
     * @throws 404
     * @throws 406
     */
    protected function getId($id, $offset = 0, $limit = self::DEFAULT_LIMIT) {
        $this->checkLimitValue($limit);

        $chunk = $this->getTemporaryFileContent($id, $offset, $limit);
        $size  = $this->getTemporaryFileSize($id);

        $this->sendAllowHeadersForArtifactFilesId();
        $this->sendPaginationHeaders($limit, $offset, $size);

        $file_data_representation = new FileDataRepresentation();

        return $file_data_representation->build($chunk);
    }

    /**
     * @throws 401
     * @throws 404
     */
    private function getTemporaryFileContent($id, $offset, $limit) {
        $file = $this->getFile($id);

        try {
            return $this->file_manager->getTemporaryFileChunk($file, $offset, $limit);

        } catch (FileNotFoundException $e) {
            throw new RestException(404);
        }
    }

    /**
     * @throws 404
     */
    private function getTemporaryFileSize($id) {
        return $this->getFile($id)->getSize();
    }

    /**
     * Create a temporary file
     *
     * Call this method to create a new file. To add new chunks, use PUT on /artifact_temporary_files/:id
     *
     * <p>Limitations:</p>
     * <pre>
     * * Size of each chunk cannot exceed 1MB<br>
     * * Total size of temporary files cannot exceed a given quota. Default is 64MB, but it depends on the settings of <br>
     * &nbsp; your platform. See X-QUOTA and X-DISK-USAGE custom headers to know the quota and your usage.
     * </pre>
     *
     * @url POST
     * @param string $name          Name of the file {@from body}
     * @param string $mimetype      Mime-Type of the file {@from body}
     * @param string $content       First chunk of the file (base64-encoded) {@from body}
     * @param string $description   Description of the file {@from body}
     *
     * @return \Tuleap\Tracker\REST\Artifact\FileInfoRepresentation
     * @throws 500 406 403
     */
    protected function post($name, $mimetype, $content, $description = null) {
        try {
            $this->file_manager->validateChunkSize($content);

            $file         = $this->file_manager->save($name, $description, $mimetype);
            $chunk_offset = 1;
            $append       = $this->file_manager->appendChunk($content, $file, $chunk_offset);
        } catch (CannotCreateException $e) {
            $this->raiseError(500);
        } catch (ChunkTooBigException $e) {
            $this->raiseError(406, 'Uploaded content exceeds maximum size of ' . $this->file_manager->getMaximumChunkSize());
        } catch (InvalidPathException $e) {
            $this->raiseError(500, $e->getMessage());
        } catch (QuotaExceededException $e) {
            $this->raiseError(406, 'You exceeded your quota. Please remove existing temporary files before continuing.');
        }

        if (! $append) {
            $this->raiseError(500);
        }

        $this->sendAllowHeadersForArtifactFiles();

        return $this->buildFileRepresentation($file);
    }

    /**
     * Append a chunk to a temporary file (not attached to any artifact)
     *
     * Use this method to append a file chunk to any existing file created via POST on /artifact_temporary_files
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
     * @url PUT {id}
     *
     * @param int    $id      The ID of the temporary artifact_file
     * @param string $content Chunk of the file (base64-encoded) {@from body}
     * @param int    $offset  Used to check that the chunk uploaded is the next one (minimum value is 2) {@from body}
     *
     * @return \Tuleap\Tracker\REST\Artifact\FileInfoRepresentation
     * @throws 406
     */
    protected function putId($id, $content, $offset) {
        $this->checkFileIsTemporary($id);

        $file = $this->getFile($id);

        try {
            $this->file_manager->validateChunkSize($content);
            $this->file_manager->appendChunk($content, $file, $offset);

        } catch (ChunkTooBigException $e) {
            $this->raiseError(406, 'Uploaded content exceeds maximum size of ' . $this->file_manager->getMaximumChunkSize());
        } catch (InvalidOffsetException $e) {
            $this->raiseError(406, 'Invalid offset received. Expected: '. ($file->getCurrentChunkOffset() +1));
        } catch (QuotaExceededException $e) {
            $this->raiseError(406, 'You exceeded your quota. Please remove existing temporary files before continuing.');
        }

        $this->sendAllowHeadersForArtifactFilesId();

        return $this->buildFileRepresentation($file);
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        $this->sendAllowHeadersForArtifactFiles();
    }

    /**
     * @url OPTIONS {id}
     *
     * @throws 401
     * @throws 404
     */
    public function optionsId($id) {
        $this->sendAllowHeadersForArtifactFilesId();
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
            $file->getType(),
            null,
            null
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
            $this->raiseError(404);
        }

        $this->checkTemporaryFileBelongsToCurrentUser($file);

        return $file;
    }

    /**
     * @throws 401
     */
    private function checkTemporaryFileBelongsToCurrentUser(TemporaryFile $file) {
        $creator_id = $file->getCreatorId();

        if ($creator_id != $this->user->getId()) {
            $this->raiseError(401, 'This file does not belong to you');
        }
    }

    /**
     * Delete a temporary file
     *
     * Use this method to delete a temporary file the user owns.
     *
     * @url DELETE {id}
     *
     * @throws 500, 400
     *
     * @param string $id Id of the file
     */
    protected function deleteId($id) {
        $this->checkFileIsTemporary($id);
        $this->sendAllowHeadersForArtifactFilesId();

        try {
            $this->removeTemporaryFile($id);

        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            $this->raiseError(400, $exception->getMessage());
        } catch (InvalidFileInfoException $exception) {
            $this->raiseError(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                $this->raiseError(500, $GLOBALS['Response']->getRawFeedback());
            }
            $this->raiseError(500, $exception->getMessage());
        }

        $this->sendAllowHeadersForArtifactFiles();
    }

    private function sendAllowHeadersForArtifactFiles() {
        Header::allowOptionsGetPost();
        $this->sendSizeHeaders();
    }

    private function sendAllowHeadersForArtifactFilesId() {
        Header::allowOptionsGetPutDelete();
        $this->sendSizeHeaders();
    }

    private function sendSizeHeaders() {
        Header::sendQuotaHeader($this->file_manager->getQuota());
        Header::sendDiskUsage($this->file_manager->getDiskUsage());
        Header::sendMaxFileChunkSizeHeaders($this->file_manager->getMaximumChunkSize());
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, $this->file_manager->getMaximumChunkSize());
    }

    /**
     * @param int $fileinfo_id
     *
     * @return Tracker_Artifact
     */
    private function getArtifactByFileInfoId($fileinfo_id) {
        try {
            $artifact = $this->fileinfo_factory->getArtifactByFileInfoIdInLastChangeset($this->user, $fileinfo_id);
        } catch (InvalidFileInfoException $e) {
            $this->raiseError(404, $e->getMessage());
        } catch (UnauthorisedException $e) {
            $this->raiseError(403, $e->getMessage());
        }

        if ($artifact) {
            ProjectAuthorization::userCanAccessProject($this->user, $artifact->getTracker()->getProject(), new Tracker_URLVerification());
            return $artifact;
        }
    }

    private function removeTemporaryFile($id) {
        $file = $this->getFile($id);
        $this->file_manager->removeTemporaryFile($file);
    }

    private function checkFileIsTemporary($id) {
        if (! $this->file_manager->isFileIdTemporary($id)) {
            $this->raiseError(404);
        }
    }

    /**
     * @throws 406
     */
    private function checkLimitValue($limit) {
        if ($limit > self::DEFAULT_LIMIT) {
            throw new LimitOutOfBoundsException(self::DEFAULT_LIMIT);
        }
    }

    private function raiseError($status_code, $message = null) {
        $this->sendAllowHeadersForArtifactFiles();

        throw new RestException($status_code, $message);
    }
}
