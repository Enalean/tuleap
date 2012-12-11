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

class Tracker_SOAP_TemporaryFile {
    const TEMP_FILE_PREFIX = 'soap_attachement_temp_';
    const TEMP_FILE_NB_MAX = 5;

    private $user;
    private $attachment_name;

    public function __construct(User $user, $attachment_name = null) {
        $this->user = $user;
        $this->attachment_name = $attachment_name;
    }

    public function exists() {
        return file_exists($this->getPath());
    }

    public function getUserTemporaryFilePrefix() {
        return self::TEMP_FILE_PREFIX.$this->user->getId().'_';
    }

    public function getPath() {
        return Config::get('codendi_cache_dir').DIRECTORY_SEPARATOR.$this->getUserTemporaryFilePrefix().$this->attachment_name;
    }

    public function getUserTemporaryFiles() {
        return glob(Config::get('codendi_cache_dir').DIRECTORY_SEPARATOR.$this->getUserTemporaryFilePrefix().'*');
    }

    public function isOverUserTemporaryFileLimit() {
        return count($this->getUserTemporaryFiles()) > (self::TEMP_FILE_NB_MAX - 1);
    }

    public function getTemporaryFilesSize() {
        $size  = 0;
        $files = $this->getUserTemporaryFiles();
        foreach ($files as $file) {
            $size = $size + filesize($file);
        }
        return $size;
    }

    public function getUniqueFileName() {
        if ($this->isOverUserTemporaryFileLimit()) {
            return new SoapFault(nb_max_temp_files, 'Temporary attachment limits: '.self::TEMP_FILE_NB_MAX.' files max.');
        }
        $prefix       = $this->getUserTemporaryFilePrefix();
        $file_path    = tempnam(Config::get('codendi_cache_dir'), $prefix);
        return substr(basename($file_path), strlen($prefix));
    }

    public function appendChunk($content) {
        $attachment_path = $this->getPath();
        if (file_exists($attachment_path)) {
            if ($this->validTemporaryFilesSize($content)) {
                return file_put_contents($attachment_path, $content, FILE_APPEND);
            } else {
                return new SoapFault(uploaded_file_too_big, 'Uploaded file exceed max file size for attachments ('.Config::get('sys_max_size_upload').')');
            }
        } else {
            return new SoapFault(temp_file_invalid, 'Invalid temporary file path');
        }
    }

    private function validTemporaryFilesSize($content) {
        $chunk_size = strlen($content);
        $total_size = $chunk_size + $this->getTemporaryFilesSize();

        return $total_size <= Config::get('sys_max_size_upload');
    }
}

?>
