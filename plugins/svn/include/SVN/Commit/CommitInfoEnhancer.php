<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVNCore\Repository;

class CommitInfoEnhancer
{
    private $commit_info;
    private $svn_look;

    public function __construct(Svnlook $svn_look, CommitInfo $commit_info)
    {
        $this->svn_look    = $svn_look;
        $this->commit_info = $commit_info;
    }

    /**
     * @return CommitInfo
     */
    public function getCommitInfo()
    {
        return $this->commit_info;
    }

    public function enhance(Repository $repository, $revision)
    {
        $this->setGeneralCommitInfo($repository, $revision);
        $this->setChangedFiles($repository, $revision);
        $this->setChangedDirectories($repository, $revision);
    }

    public function enhanceWithTransaction(Repository $repository, $transaction)
    {
        $this->commit_info->setCommitMessage(
            implode(
                "\n",
                $this->svn_look->getMessageFromTransaction($repository, $transaction)
            )
        );
    }

    public function setTransactionPath(Repository $repository, $revision)
    {
        if ($this->checkRepositoryExists($repository)) {
            $transaction_path = $this->svn_look->getTransactionPath($repository, $revision);
            $this->commit_info->setTransactionPath($transaction_path);
        } else {
            throw new CannotFindRepositoryException(dgettext('tuleap-svn', 'Repository not found'));
        }
    }

    private function setGeneralCommitInfo(Repository $repository, $revision)
    {
        if ($this->checkRepositoryExists($repository)) {
            $info_commit = $this->svn_look->getInfo($repository, $revision);
            $this->commit_info->setUser($info_commit[0]);
            $this->commit_info->setDate($info_commit[1]);
            $this->commit_info->setCommitMessage(implode("\n", array_slice($info_commit, 2)));
        } else {
            throw new CannotFindRepositoryException(dgettext('tuleap-svn', 'Repository not found'));
        }
    }

    private function setChangedFiles(Repository $repository, $revision)
    {
        if ($this->checkRepositoryExists($repository)) {
            $file_added    = [];
            $file_updated  = [];
            $file_deleted  = [];
            $changed_files = $this->svn_look->getChangedFiles($repository, $revision);
            if ($changed_files != "") {
                foreach ($changed_files as $file) {
                    if (preg_match("/^([A|U|D][ ]+)(.+)$/", $file, $matches)) {
                        switch (trim($matches[1])) {
                            case "A":
                                $file_added[] = $matches[2];
                                break;
                            case 'U':
                                $file_updated[] = $matches[2];
                                break;
                            case 'D':
                                $file_deleted[] = $matches[2];
                                break;
                        }
                    }
                }
                $this->commit_info->setUpdatedFiles($file_updated);
                $this->commit_info->setAddedFiles($file_added);
                $this->commit_info->setDeletedFiles($file_deleted);
            } else {
                throw new CannotFindSVNCommitInfoException(
                    dgettext('tuleap-svn', 'Cannot find changed files information')
                );
            }
        } else {
            throw new CannotFindRepositoryException(dgettext('tuleap-svn', 'Repository not found'));
        }
    }

    private function setChangedDirectories(Repository $repository, $revision)
    {
        if ($this->checkRepositoryExists($repository)) {
            $changed_dir = $this->svn_look->getChangedDirectories($repository, $revision);
            if (count($changed_dir) > 0) {
                $this->commit_info->setChangedDirectories($changed_dir);
            } else {
                throw new CannotFindSVNCommitInfoException(
                    dgettext('tuleap-svn', 'Cannot find changed directories information')
                );
            }
        } else {
            throw new CannotFindRepositoryException(dgettext('tuleap-svn', 'Repository not found'));
        }
    }

    /**
     * @return bool
     */
    private function checkRepositoryExists(Repository $repository)
    {
        return $repository instanceof Repository && $repository->getID() !== null;
    }
}
