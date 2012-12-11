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
}

?>
