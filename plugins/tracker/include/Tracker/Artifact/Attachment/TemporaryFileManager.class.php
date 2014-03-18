<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

/**
 * Manage temporary uploaded files
 */
class Tracker_Artifact_Attachment_TemporaryFileManager {
    const TEMP_FILE_PREFIX = 'soap_attachement_temp_';
    const TEMP_FILE_NB_MAX = 5;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Tracker_Artifact_Attachment_TemporaryFileManagerDao
     */
    private $dao;

    public function __construct(PFUser $user, Tracker_Artifact_Attachment_TemporaryFileManagerDao $dao) {
        $this->user = $user;
        $this->dao  = $dao;
    }

    /**
     * Does the temporary file exists on filesystem
     *
     * @return Boolean
     */
    public function exists($attachment_name) {
        return file_exists($this->getPath($attachment_name));
    }

    /**
     * Return full path to the file on filesystem
     *
     * @return String
     */
    public function getPath($attachment_name) {
        return Config::get('codendi_cache_dir').DIRECTORY_SEPARATOR.$this->getUserTemporaryFilePrefix().$attachment_name;
    }

    /**
     * Provision a new temporary file for user if possible and return it's UUID
     *
     * @return String
     * @throws Tracker_Artifact_Attachment_MaxFilesException
     */
    public function getUniqueFileName() {
        if ($this->isOverUserTemporaryFileLimit()) {
            throw new Tracker_Artifact_Attachment_MaxFilesException('Temporary attachment limits: '.self::TEMP_FILE_NB_MAX.' files max.');
        }
        $prefix    = $this->getUserTemporaryFilePrefix();
        $file_path = tempnam(Config::get('codendi_cache_dir'), $prefix);
        return substr(basename($file_path), strlen($prefix));
    }

    /**
     * @return Tracker_Artifact_Attachment_TemporaryFile
     * @throws Tracker_Artifact_Attachment_CannotCreateException
     */
    public function save($name, $description, $mimetype) {
        $user_id   = $this->user->getId();
        $tempname  = $this->getUniqueFileName();
        $timestamp = $_SERVER['REQUEST_TIME'];

        $id = $this->dao->create($user_id, $name, $description, $mimetype, $timestamp, $tempname);

        if (! $id) {
            throw new Tracker_Artifact_Attachment_CannotCreateException();
        }

        return new Tracker_Artifact_Attachment_TemporaryFile($id, $name, $tempname, $description, $timestamp);
    }

    /**
     * Append some content (base64 encoded) to the file
     *
     * @param String $content
     * @param String $attachment_name
     * @return Number of written bytes on filesystem
     * @throws Tracker_Artifact_Attachment_FileTooBigException
     * @throws Tracker_Artifact_Attachment_InvalidPathException
     */
    public function appendChunkForSOAP($content, $attachment_name) {
        return $this->appendChunk($content, $attachment_name);
    }

    /**
     * Append some content (base64 encoded) to the file
     *
     * @param String $content
     * @param Tracker_Artifact_Attachment_TemporaryFile $file
     * @return boolean
     * @throws Tracker_Artifact_Attachment_FileTooBigException
     * @throws Tracker_Artifact_Attachment_InvalidPathException
     */
    public function appendChunkForREST($content, Tracker_Artifact_Attachment_TemporaryFile $file) {
        $bytes_written = $this->appendChunk($content, $file->getTemporaryName());

        return $bytes_written && $this->dao->updateLastModifiedDate($file->getId(), $_SERVER['REQUEST_TIME']);
    }

    private function appendChunk($content, $attachment_name) {
        $decoded_content = base64_decode($content);
        if ($this->exists($attachment_name)) {
            if ($this->validTemporaryFilesSize($decoded_content)) {
                return file_put_contents($this->getPath($attachment_name), $decoded_content, FILE_APPEND);
            } else {
                throw new Tracker_Artifact_Attachment_FileTooBigException('Uploaded file exceed max file size for attachments ('.Config::get('sys_max_size_upload').')');
            }
        } else {
            throw new Tracker_Artifact_Attachment_InvalidPathException('Invalid temporary file path');
        }
    }

    /**
     * Remove all temporary files of user
     *
     * @return Boolean
     */
    public function purgeAllTemporaryFiles() {
        foreach ($this->getUserTemporaryFiles() as $file) {
            unlink($file);
        }
        return true;
    }

    private function getUserTemporaryFiles() {
        return glob(Config::get('codendi_cache_dir').DIRECTORY_SEPARATOR.$this->getUserTemporaryFilePrefix().'*');
    }

    private function isOverUserTemporaryFileLimit() {
        return count($this->getUserTemporaryFiles()) > (self::TEMP_FILE_NB_MAX - 1);
    }

    private function getUserTemporaryFilePrefix() {
        return self::TEMP_FILE_PREFIX.$this->user->getId().'_';
    }

    private function getTemporaryFilesSize() {
        $size  = 0;
        $files = $this->getUserTemporaryFiles();
        foreach ($files as $file) {
            $size = $size + filesize($file);
        }
        return $size;
    }

    private function validTemporaryFilesSize($content) {
        $chunk_size = strlen($content);
        $total_size = $chunk_size + $this->getTemporaryFilesSize();

        return $total_size <= self::getMaximumFileChunkSize();
    }

    public static function getMaximumFileChunkSize() {
        return Config::get('sys_max_size_upload');
    }
}

?>
