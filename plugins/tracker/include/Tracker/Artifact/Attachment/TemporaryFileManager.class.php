<?php

/**
 * Copyright (c) Enalean, 2014 — 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Tracker\Artifact\Attachment\QuotaExceededException;
use Tuleap\Tracker\Artifact\Attachment\PaginatedTemporaryFiles;

/**
 * Manage temporary uploaded files
 */
class Tracker_Artifact_Attachment_TemporaryFileManager {

    const TEMP_FILE_PREFIX = 'rest_attachement_temp_';

    /**
     * @var Tracker_Artifact_Attachment_TemporaryFileManagerDao
     */
    private $dao;

    /**
     * @var Tracker_FileInfoFactory
     */
    private $file_info_factory;

    /**
     * @var System_Command
     */
    private $system;

    /**
     * @var int
     */
    private $retention_delay_in_days;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        UserManager $user_manager,
        Tracker_Artifact_Attachment_TemporaryFileManagerDao $dao,
        Tracker_FileInfoFactory $file_info_factory,
        System_Command $system,
        $retention_delay
    ) {
        $this->dao                     = $dao;
        $this->file_info_factory       = $file_info_factory;
        $this->system                  = $system;
        $this->retention_delay_in_days = $retention_delay;
        $this->user_manager            = $user_manager;
    }

    public function purgeOldTemporaryFiles() {
        $timestamp = $_SERVER['REQUEST_TIME'] - $this->retention_delay_in_days * 3600 * 24;

        $old_files = $this->dao
            ->searchTemporaryFilesOlderThan($timestamp)
            ->instanciateWith(array($this, 'getInstanceFromRow'));

        foreach ($old_files as $file) {
            $this->removeTemporaryFile($file);
        }
    }

    /**
     * Does the temporary file exists on filesystem
     *
     * @return Boolean
     */
    public function exists(PFUser $user, $attachment_name) {
        return file_exists($this->getPath($user, $attachment_name));
    }

    /**
     * Return full path to the file on filesystem
     *
     * @return String
     */
    public function getPath(PFUser $user, $attachment_name) {
        return ForgeConfig::get('codendi_cache_dir') . DIRECTORY_SEPARATOR . $this->getUserTemporaryFilePrefix($user) . $attachment_name;
    }

    /**
     * Provision a new temporary file for user if possible and return it's UUID
     *
     * @return String
     */
    private function getUniqueFileName(PFUser $user) {
        $prefix = $this->getUserTemporaryFilePrefix($user);
        $file_path = tempnam(ForgeConfig::get('codendi_cache_dir'), $prefix);
        return substr(basename($file_path), strlen($prefix));
    }

    /**
     * @throws Tracker_Artifact_Attachment_CannotCreateException
     * @throws Tuleap\Tracker\Artifact\Attachment\QuotaExceededException
     *
     * @return \Tracker_Artifact_Attachment_TemporaryFile
     */
    public function save(PFUser $user, $name, $description, $mimetype) {
        $chunk_size = 0;
        $this->checkThatChunkSizeIsNotOverTheQuota($user, $chunk_size);

        $user_id   = $user->getId();
        $tempname  = $this->getUniqueFileName($user);
        $timestamp = $_SERVER['REQUEST_TIME'];

        $id = $this->dao->create($user_id, $name, $description, $mimetype, $timestamp, $tempname);

        if (!$id) {
            throw new Tracker_Artifact_Attachment_CannotCreateException();
        }

        $number_of_chunks = 0;
        $filesize = 0;

        return new Tracker_Artifact_Attachment_TemporaryFile(
            $id,
            $name,
            $tempname,
            $description,
            $timestamp,
            $number_of_chunks,
            $user->getId(),
            $filesize,
            $mimetype
        );
    }

    /**
     * Get chunk of a file
     *
     * TO_REFACTOR: This has nothing to do with temporary file. Should be moved in a dedicated object.
     *
     * @param int    $attachment_id
     * @param PFUser $current_user
     * @param int    $offset
     * @param int    $size
     *
     * @return \Tracker_Artifact_Attachment_PermissionDeniedOnFieldException
     *
     * @throws Tracker_Artifact_Attachment_PermissionDeniedOnFieldException
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    public function getAttachedFileChunk($attachment_id, PFUser $current_user, $offset, $size) {
        $file_info = $this->file_info_factory->getById($attachment_id);

        if ($file_info && $file_info->fileExists()) {
            $field = $file_info->getField();

            if ($field->userCanRead($current_user)) {
                return $file_info->getContent($offset, $size);

            } else {
                throw new Tracker_Artifact_Attachment_PermissionDeniedOnFieldException('Permission denied: you cannot access this field');
            }
        }

        throw new Tracker_Artifact_Attachment_FileNotFoundException();
    }

    /**
     * Returns encoded content chunk of file
     *
     * @param Tracker_Artifact_Attachment_TemporaryFile $file
     * @param int $offset Where to start reading
     * @param int $size   How much to read
     *
     * @return string Base64 encoded content
     *
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    public function getTemporaryFileChunk(Tracker_Artifact_Attachment_TemporaryFile $file, $offset, $size) {
        $temporary_name = $file->getTemporaryName();

        $user = $this->user_manager->getUserById($file->getCreatorId());
        if ($this->exists($user, $temporary_name)) {
            return base64_encode(file_get_contents($this->getPath($user, $temporary_name), false, NULL, $offset, $size));
        }

        throw new Tracker_Artifact_Attachment_FileNotFoundException();
    }

    /**
     * Append some content (base64 encoded) to the file
     *
     * @param String $content
     * @param Tracker_Artifact_Attachment_TemporaryFile $file
     * @param int $offset
     *
     * @return boolean
     * @throws Tracker_Artifact_Attachment_InvalidPathException
     * @throws Tracker_Artifact_Attachment_InvalidOffsetException
     */
    public function appendChunk($content, Tracker_Artifact_Attachment_TemporaryFile $file, $offset) {
        $current_offset = $file->getCurrentChunkOffset();

        if ($current_offset + 1 !== (int) $offset) {
            throw new Tracker_Artifact_Attachment_InvalidOffsetException();
        }

        $user = $this->user_manager->getUserById($file->getCreatorId());
        if (! $this->exists($user, $file->getTemporaryName())) {
            throw new Tracker_Artifact_Attachment_InvalidPathException('Invalid temporary file path');
        }

        $this->checkThatChunkSizeIsNotOverTheQuota($user, $content);
        $path = $this->getPath($user, $file->getTemporaryName());
        $bytes_written = file_put_contents($path, base64_decode($content), FILE_APPEND);

        $size = (int) implode('', $this->system->exec('stat -c %s ' . escapeshellarg($path)));
        $file->setSize($size);

        return $bytes_written && $this->dao->updateFileInfo($file->getId(), $offset, $_SERVER['REQUEST_TIME'], $size);
    }

    /**
     * @return Tracker_Artifact_Attachment_TemporaryFile[]
     */
    public function getPaginatedUserTemporaryFiles(PFUser $user, $offset, $limit) {
        $files = $this->dao
            ->searchPaginatedUserTemporaryFiles($user->getId(), $offset, $limit)
            ->instanciateWith(array($this, 'getInstanceFromRow'));

        return new PaginatedTemporaryFiles($files, $this->dao->foundRows());
    }

    /**
     * @return int
     */
    public function getDiskUsage(PFUser $user) {
        $size = 0;
        foreach (glob($this->getPath($user, '*')) as $file) {
            $size += (int) implode('', $this->system->exec('stat -c %s ' . escapeshellarg($file)));
        }

        return $size;
    }

    /**
     * @throws Tuleap\Tracker\Artifact\Attachment\QuotaExceededException
     */
    private function checkThatChunkSizeIsNotOverTheQuota(PFUser $user, $content) {
        $chunk_size = strlen(base64_decode($content));
        if ($this->getDiskUsage($user) + $chunk_size > $this->getQuota()) {
            throw new QuotaExceededException();
        }
    }

    private function getUserTemporaryFilePrefix(PFUser $user) {
        return self::TEMP_FILE_PREFIX . $user->getId() . '_';
    }

    /**
     * TO_REFACTOR: This has nothing to do with temporary file. Should be moved in a dedicated object.
     */
    public function getAttachedFileSize($id) {
        $file_info = $this->file_info_factory->getById($id);

        if ($file_info && $file_info->fileExists()) {
            return $file_info->getFilesize();
        }

        throw new Tracker_Artifact_Attachment_FileNotFoundException();
    }

    /**
     * @throws Tracker_Artifact_Attachment_ChunkTooBigException
     */
    public function validateChunkSize(PFUser $user, $content) {
        $chunk_size = strlen(base64_decode($content));

        if ($chunk_size > $this->getMaximumChunkSize()) {
            throw new Tracker_Artifact_Attachment_ChunkTooBigException();
        }

        $this->checkThatChunkSizeIsNotOverTheQuota($user, $content);
    }

    /**
     * Max chunk size : 1 Mo = 1048576 bytes
     */
    public function getMaximumChunkSize() {
        return 1048576;
    }

    /**
     * Max chunk size : 64 Mo = 67108864 bytes
     */
    public function getQuota() {
        return ForgeConfig::get('sys_max_size_upload');
    }

    /**
     * @return \Tracker_Artifact_Attachment_TemporaryFile
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    public function getFile($id) {
        $row = $this->dao->getTemporaryFile($id);

        if (! $row) {
            throw new Tracker_Artifact_Attachment_FileNotFoundException();
        }

        return $this->getInstanceFromRow($row);
    }

    public function getFileByTemporaryName($temporary_name) {
        $row = $this->dao->getTemporaryFileByTemporaryName($temporary_name);

        if (! $row) {
            return;
        }

        return $this->getInstanceFromRow($row);
    }

    public function isFileIdTemporary($id) {
        return $this->dao->doesFileExist($id);
    }

    public function removeTemporaryFile(Tracker_Artifact_Attachment_TemporaryFile $file) {
        $this->removeTemporaryFileInDB($file->getId());
        $this->removeTemporaryFileFomFileSystem($file);
    }

    private function removeTemporaryFileInDB($id) {
        $this->dao->delete($id);
    }

    public function removeTemporaryFileInDBByTemporaryName($temporary_name) {
        $this->dao->deleteByTemporaryName($temporary_name);
    }

    private function removeTemporaryFileFomFileSystem(Tracker_Artifact_Attachment_TemporaryFile $temporary_file) {
        $user = $this->user_manager->getUserById($temporary_file->getCreatorId());

        $temporary_file_name = $temporary_file->getTemporaryName();
        $temporary_file_path = $this->getPath($user, $temporary_file_name);

        if ($this->exists($user, $temporary_file_name)) {
            unlink($temporary_file_path);
        }
    }

    public function getInstanceFromRow($row) {
        return new Tracker_Artifact_Attachment_TemporaryFile(
            $row['id'],
            $row['filename'],
            $row['tempname'],
            $row['description'],
            $row['last_modified'],
            $row['offset'],
            $row['submitted_by'],
            $row['filesize'],
            $row['filetype']
        );
    }

    public function getAlreadyAttachedFileIds(Tracker_Artifact $artifact) {
        $formelement_factory     = Tracker_FormElementFactory::instance();
        $formelement_files       = $formelement_factory->getUsedFormElementsByType($artifact->getTracker(), 'file');

        $last_changeset_file_ids = array();

        foreach($formelement_files as $formelement_files) {
            $field = $formelement_factory->getFormElementById($formelement_files->getId());
            $value = $field->getLastChangesetValue($artifact);

            if ($value) {
                foreach($value->getFiles() as $file) {
                    $last_changeset_file_ids[] = (int) $file->getId();
                }
            }
        }

        return $last_changeset_file_ids;
    }

    /**
     * Get the field data for artifact submission
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     * @throws Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException
     */
    public function buildFieldDataForREST($rest_value, Tracker_Artifact $artifact = null) {
        $field_data                = array();
        $already_attached_file_ids = array();

        if ($artifact) {
            $already_attached_file_ids = $this->getAlreadyAttachedFileIds($artifact);
        }

        $given_rest_file_ids = $rest_value->value;
        // Ids given in REST
        foreach ($given_rest_file_ids as $file_id) {
            $linked_artifact = $this->file_info_factory->getArtifactByFileInfoIdInLastChangeset($file_id);

            // Temporary => link
            if (! $linked_artifact && $this->isFileIdTemporary($file_id)) {
                $temporary_file = $this->getFile($file_id);

                $user = $this->user_manager->getUserById($temporary_file->getCreatorId());
                if (! $this->exists($user, $temporary_file->getTemporaryName())) {
                    throw new Tracker_Artifact_Attachment_FileNotFoundException('Temporary file #' . $file_id . ' not found');
                }

                $field_data[] = $this->file_info_factory->buildFileInfoData(
                    $temporary_file,
                    $this->getPath($user, $temporary_file->getTemporaryName())
                );

            } elseif (! $linked_artifact && ! $this->isFileIdTemporary($file_id)) {
                throw new Tracker_Artifact_Attachment_FileNotFoundException('Temporary file #' . $file_id . ' not found');

            // Already attached to another artifact => error
            } elseif ($artifact && $artifact->getId() != $linked_artifact->getId()
                    || ! $artifact && $linked_artifact) {

                throw new Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException('File #' . $file_id . ' is already linked to artifact #' . $linked_artifact->getId());
            }
        }

        // Already attached file ids
        foreach ($already_attached_file_ids as $file_id) {
            // Not in given ids => unlink
            if (! in_array($file_id, $given_rest_file_ids)) {
                $field_data['delete'][] = $file_id;
            }
        }

        return $field_data;
    }
}

?>
