<?php
/**
 * Copyright Enalean (c) 2016 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\SVN\Commit;

class CommitInfo
{

    private $commit_message;
    private $user;
    private $date;
    private $file_added;
    private $file_updated;
    private $file_deleted;
    private $directories;
    private $transaction_path;

    public function setTransactionPath(array $transaction_path)
    {
        return $this->transaction_path = $transaction_path;
    }

    public function setCommitMessage($commit_message)
    {
        return $this->commit_message = $commit_message;
    }

    public function setDate($date)
    {
        return $this->date = $date;
    }

    public function setUser($user)
    {
        return $this->user = $user;
    }

    public function setAddedFiles(array $file_added)
    {
        return $this->file_added = $file_added;
    }

    public function setUpdatedFiles(array $file_updated)
    {
        return $this->file_updated = $file_updated;
    }

    public function setDeletedFiles(array $file_deleted)
    {
        return $this->file_deleted = $file_deleted;
    }

    public function setChangedDirectories($directories)
    {
        return $this->directories = $directories;
    }

    public function getChangedDirectories()
    {
        return $this->directories;
    }

    public function getCommitMessage()
    {
        return $this->commit_message;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getAddedFiles()
    {
        return $this->file_added;
    }

    public function getUpdatedFiles()
    {
        return $this->file_updated;
    }

    public function getDeletedFiles()
    {
        return $this->file_deleted;
    }

    public function getDirectories()
    {
        return $this->directories;
    }

    public function getAllFiles()
    {
        return $this->getAddedFiles() + $this->getUpdatedFiles() + $this->getDeletedFiles();
    }

    public function getTransactionPath()
    {
        return $this->transaction_path;
    }

    public function hasChangedFiles()
    {
        return count($this->getAllFiles()) > 0;
    }
}
