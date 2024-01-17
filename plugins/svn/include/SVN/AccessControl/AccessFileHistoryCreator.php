<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\AccessControl;

use Tuleap\SVNCore\SVNAccessFileContent;
use Tuleap\SVNCore\SVNAccessFileWriteFault;
use Tuleap\SVNCore\SVNAccessFileWriter;
use Tuleap\SVNCore\SVNAccessFileSectionParser;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVNCore\Repository;
use Tuleap\SVNCore\CollectionOfSVNAccessFileFaults;
use Tuleap\SVNCore\SVNAccessFileContentAndFaults;
use Tuleap\SVNCore\SVNAccessFileDefaultBlockGeneratorInterface;

class AccessFileHistoryCreator
{
    public function __construct(
        private readonly AccessFileHistoryDao $dao,
        private readonly AccessFileHistoryFactory $access_file_factory,
        private readonly \ProjectHistoryDao $project_history_dao,
        private readonly ProjectHistoryFormatter $project_history_formatter,
        private readonly SVNAccessFileDefaultBlockGeneratorInterface $default_block_generator,
    ) {
    }

    /**
     * @throws CannotCreateAccessFileHistoryException
     */
    public function create(Repository $repository, $content, $timestamp): CollectionOfSVNAccessFileFaults
    {
        [$file_history, $faults] = $this->storeInDB($repository, $content, $timestamp);
        $this->logHistory($repository, $content);

        $this->saveAccessFile($repository, $file_history);

        return $faults;
    }

    public function useAVersion(Repository $repository, $version_id)
    {
        $this->useVersion($repository, $version_id, false);
    }

    public function useAVersionWithHistory(Repository $repository, $version_id)
    {
        $version = $this->access_file_factory->getById($version_id, $repository);
        $this->useVersion($repository, $version->getId(), true);
    }

    public function useAVersionWithHistoryWithoutUpdateSVNAccessFile(Repository $repository, $version_id)
    {
        try {
            $version = $this->access_file_factory->getByVersionNumber($version_id, $repository);
            $this->saveUsedVersion($repository, $version->getId());
            $this->logUseAVersionHistory($repository, $version);
        } catch (AccessFileHistoryNotFoundException $e) {
        }
    }

    private function useVersion(Repository $repository, $version_id, $log_history)
    {
        $this->saveUsedVersion($repository, $version_id);

        $current_version = $this->access_file_factory->getCurrentVersion($repository);

        $this->saveAccessFile($repository, $current_version);

        if ($log_history) {
            $this->logUseAVersionHistory($repository, $current_version);
        }
    }

    private function cleanContent(Repository $repository, string $content): SVNAccessFileContentAndFaults
    {
        $access_file         = new SVNAccessFileSectionParser();
        $access_file_content = new SVNAccessFileContent(
            $this->default_block_generator->getDefaultBlock($repository->getProject())->content,
            trim($content),
        );
        return $access_file->parse($access_file_content);
    }

    /**
     * @throws CannotCreateAccessFileHistoryException
     */
    public function saveAccessFile(Repository $repository, AccessFileHistory $history): void
    {
        $access_file_writer  = new SVNAccessFileWriter();
        $access_file_content = new SVNAccessFileContent(
            $this->default_block_generator->getDefaultBlock($repository->getProject())->content,
            $history->getContent(),
        );
        $access_file_writer->writeWithDefaults(
            $repository,
            $access_file_content,
        )->mapErr(fn (SVNAccessFileWriteFault $fault) => throw new CannotCreateAccessFileHistoryException(
            sprintf(dgettext('tuleap-svn', 'Unable to write into file %1$s'), $fault->access_file)
        ));
    }

    /**
     * @throws CannotCreateAccessFileHistoryException
     *
     * @psalm-return array{AccessFileHistory, CollectionOfSVNAccessFileFaults}
     */
    public function storeInDB(Repository $repository, string $content, int $timestamp): array
    {
        $svn_access_file = $this->cleanContent($repository, $content);
        $file_history    = $this->storeInDBWithoutCleaningContent(
            $repository,
            $svn_access_file->contents,
            $timestamp,
        );
        return [$file_history, $svn_access_file->faults];
    }

    /**
     * @throws CannotCreateAccessFileHistoryException
     */
    public function storeInDBWithoutCleaningContent(Repository $repository, string $content, int $timestamp): AccessFileHistory
    {
        $last_version_number = $this->access_file_factory->getLastVersion($repository)->getVersionNumber();
        $new_version_number  = $last_version_number + 1;

        $file_history_id = $this->dao->create($new_version_number, $repository->getId(), $content, $timestamp);
        if ($file_history_id === false) {
            throw new CannotCreateAccessFileHistoryException(
                dgettext('tuleap-svn', 'Unable to update Access Control File.')
            );
        }

        return new AccessFileHistory(
            $repository,
            $file_history_id,
            $new_version_number,
            $content,
            $timestamp
        );
    }

    private function logHistory(Repository $repository, $content)
    {
        $access_file = $this->project_history_formatter->getAccessFileHistory($content);
        $this->project_history_dao->groupAddHistory(
            'svn_multi_repository_access_file_update',
            "Repository: " . $repository->getName() . PHP_EOL . $access_file,
            $repository->getProject()->getID()
        );
    }

    private function logUseAVersionHistory(Repository $repository, AccessFileHistory $version)
    {
        if ($version->getVersionNumber() === 0) {
            return;
        }

        $old_version =
            "Repository: " . $repository->getName() . PHP_EOL .
            "version #" . $version->getVersionNumber();

        $this->project_history_dao->groupAddHistory(
            'svn_multi_repository_access_file_use_version',
            $old_version,
            $repository->getProject()->getID()
        );
    }

    private function saveUsedVersion(Repository $repository, $version_id)
    {
        if (! $this->dao->useAVersion($repository->getId(), $version_id)) {
            throw new CannotCreateAccessFileHistoryException(
                dgettext('tuleap-svn', 'Unable to update Access Control File.')
            );
        }
    }
}
