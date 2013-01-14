<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * Manage the temporary uploaded files for SOAP API
 */
class Tracker_SOAP_TemporaryFile {
    const TEMP_FILE_PREFIX = 'soap_attachement_temp_';
    const TEMP_FILE_NB_MAX = 5;

    private $user;
    private $attachment_name;

    public function __construct(User $user, $attachment_name = null) {
        $this->user = $user;
        $this->attachment_name = $attachment_name;
    }

    /**
     * Does the temporary file exists on filesystem
     *
     * @return Boolean
     */
    public function exists() {
        return file_exists($this->getPath());
    }

    /**
     * Return full path to the file on filesystem
     *
     * @return String
     */
    public function getPath() {
        return Config::get('codendi_cache_dir').DIRECTORY_SEPARATOR.$this->getUserTemporaryFilePrefix().$this->attachment_name;
    }

    /**
     * Provision a new temporary file for user if possible and return it's UUID
     *
     * @return String
     * @throws SoapFault
     */
    public function getUniqueFileName() {
        if ($this->isOverUserTemporaryFileLimit()) {
            throw new SoapFault(nb_max_temp_files, 'Temporary attachment limits: '.self::TEMP_FILE_NB_MAX.' files max.');
        }
        $prefix       = $this->getUserTemporaryFilePrefix();
        $file_path    = tempnam(Config::get('codendi_cache_dir'), $prefix);
        return substr(basename($file_path), strlen($prefix));
    }

    /**
     * Append some SOAP content (base64 encoded) to the file
     *
     * @param String $content
     * @return Number of written bytes on filesystem
     * @throws SoapFault
     */
    public function appendChunk($content) {
        $decoded_content = base64_decode($content);
        if ($this->exists()) {
            if ($this->validTemporaryFilesSize($decoded_content)) {
                return file_put_contents($this->getPath(), $decoded_content, FILE_APPEND);
            } else {
                throw new SoapFault(uploaded_file_too_big, 'Uploaded file exceed max file size for attachments ('.Config::get('sys_max_size_upload').')');
            }
        } else {
            throw new SoapFault(temp_file_invalid, 'Invalid temporary file path');
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

        return $total_size <= Config::get('sys_max_size_upload');
    }
}

?>
