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
use \Tracker_Artifact_Attachment_TemporaryFileManager    as FileManager;
use \Tracker_Artifact_Attachment_TemporaryFileManagerDao as FileManagerDao;
use \Tuleap\Tracker\REST\Artifact\ArtifactFilesReference as ArtifactFilesReference;
use \Tracker_Artifact_Attachment_CannotCreateException   as CannotCreateException;
use \Tracker_Artifact_Attachment_FileTooBigException     as FileTooBigException;
use \Tracker_Artifact_Attachment_InvalidPathException    as InvalidPathException;
use \Tracker_Artifact_Attachment_MaxFilesException       as MaxFilesException;
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
     * @return \Tuleap\Tracker\REST\Artifact\ArtifactFilesReference
     * @throws 500 406 403
     */
    protected function post($name, $description, $mimetype, $content) {
        $user         = UserManager::instance()->getCurrentUser();
        $file_manager = $this->getFileManager($user);

        $this->sendAllowHeadersForArtifactFile();

        try {
            $file = $file_manager->save($name, $description, $mimetype);
            $append = $file_manager->appendChunkForREST($content, $file);
        } catch (CannotCreateException $e) {
            throw new RestException(500);
        } catch (FileTooBigException $e) {
            throw new RestException(406, 'Uploaded content exceeds maximum size of ' . FileManager::getMaximumFileChunkSize());
        } catch (InvalidPathException $e) {
            throw new RestException(500);
        } catch (MaxFilesException $e) {
            throw new RestException(403, 'Maximum number of temporary files reached: '. FileManager::TEMP_FILE_NB_MAX);
        }

        if (! $append) {
            throw new RestException(500);
        }

        $reference = new ArtifactFilesReference();
        return $reference->build($file);
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        $this->sendAllowHeadersForArtifactFile();
    }

    /**
     * @param PFUser $user
     * @return \Tracker_Artifact_Attachment_TemporaryFileManager
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
}
