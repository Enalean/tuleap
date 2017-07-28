<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Svn\AccessControl;

use Tuleap\Svn\Repository\Repository;
use SVN_AccessFile_Writer;
use SVNAccessFile;

class AccessFileHistoryCreator {

    /**
     * @var AccessFileHistoryFactory
     */
    private $access_file_factory;

    /**
     * @var AccessFileHistoryDao
     */
    private $dao;

    public function __construct(
        AccessFileHistoryDao $dao,
        AccessFileHistoryFactory $access_file_factory
    ) {
        $this->dao                 = $dao;
        $this->access_file_factory = $access_file_factory;
    }

    public function create(Repository $repository, $content, $timestamp) {
        $file_history = $this->storeInDB($repository, $content, $timestamp);

        $this->saveAccessFile($repository, $file_history);
        return $file_history;
    }

    public function useAVersion(Repository $repository, $version_id) {
        if (! $this->dao->useAVersion($repository->getId(), $version_id)) {
            throw new CannotCreateAccessFileHistoryException(
                $GLOBALS['Language']->getText('plugin_svn', 'update_access_history_file_error')
            );
        }

        $current_version = $this->access_file_factory->getCurrentVersion($repository);
        $this->saveAccessFile($repository, $current_version);
    }

    private function cleanContent(Repository $repository, $content) {
        $access_file = new SVNAccessFile();
        return trim(
            $access_file->parseGroupLinesByRepositories($repository->getSystemPath(), $content, true)
        );
    }

    private function saveAccessFile(Repository $repository, AccessFileHistory $history) {
        $accessfile = new SVN_AccessFile_Writer($repository->getSystemPath());
        if (! $accessfile->write_with_defaults($history->getContent())) {
            if ($accessfile->isErrorFile()) {
                throw new CannotCreateAccessFileHistoryException(
                    $GLOBALS['Language']->getText('plugin_svn_admin','file_error', $repository->getSystemPath())
                );
            } else {
                throw new CannotCreateAccessFileHistoryException(
                    $GLOBALS['Language']->getText('plugin_svn_admin','write_error', $repository->getSystemPath())
                );
            }
        }
    }

    /**
     * @return AccessFileHistory
     * @throws CannotCreateAccessFileHistoryException
     */
    public function storeInDB(Repository $repository, $content, $timestamp)
    {
        $id             = 0;
        $version_number = $this->access_file_factory->getLastVersion($repository)->getVersionNumber();

        $file_history = new AccessFileHistory(
            $repository,
            $id,
            $version_number + 1,
            $this->cleanContent($repository, $content),
            $timestamp
        );
        if (! $this->dao->create($file_history)) {
            throw new CannotCreateAccessFileHistoryException(
                $GLOBALS['Language']->getText('plugin_svn', 'update_access_history_file_error')
            );
        }

        return $file_history;
    }
}
