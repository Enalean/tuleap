<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\GitReference;

use GitRepositoryFactory;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\GitExec;

class GitPullRequestReferenceBulkConverter
{
    public const STOP_CONVERSION_FILE = 'tuleap_plugin_pullrequest_stop_bulk_convert';

    /**
     * @var GitPullRequestReferenceDAO
     */
    private $dao;
    /**
     * @var GitPullRequestReferenceUpdater
     */
    private $updater;
    /**
     * @var Factory
     */
    private $pull_request_factory;
    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        GitPullRequestReferenceDAO $dao,
        GitPullRequestReferenceUpdater $updater,
        Factory $pull_request_factory,
        GitRepositoryFactory $git_repository_factory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->dao                    = $dao;
        $this->updater                = $updater;
        $this->pull_request_factory   = $pull_request_factory;
        $this->git_repository_factory = $git_repository_factory;
        $this->logger                 = $logger;
    }

    public function convertAllPullRequestsWithoutAGitReference()
    {
        $pull_request_without_git_ref_rows = $this->dao->searchPullRequestsByReferenceStatus(GitPullRequestReference::STATUS_NOT_YET_CREATED);
        foreach ($pull_request_without_git_ref_rows as $pull_request_without_git_ref_row) {
            if (file_exists(\ForgeConfig::get('tmp_dir') . DIRECTORY_SEPARATOR . self::STOP_CONVERSION_FILE)) {
                $this->logger->info('Stop file found, creation of Git reference for existing PR has been stopped');
                return;
            }

            $pull_request_without_git_ref = $this->pull_request_factory->getInstanceFromRow($pull_request_without_git_ref_row);
            $pull_request_id              = $pull_request_without_git_ref->getId();

            $this->logger->debug('Try to create Git reference for PR #' . $pull_request_id);

            $repository_source_id      = $pull_request_without_git_ref->getRepositoryId();
            $repository_source         = $this->git_repository_factory->getRepositoryById($repository_source_id);
            $repository_destination_id = $pull_request_without_git_ref->getRepoDestId();
            $repository_destination    = $this->git_repository_factory->getRepositoryById($repository_destination_id);

            if ($repository_source === null || $repository_destination === null) {
                $pull_request_id = $pull_request_without_git_ref->getId();
                $this->dao->updateStatusByPullRequestId(
                    $pull_request_id,
                    GitPullRequestReference::STATUS_BROKEN
                );

                $this->logger->error(
                    "PR #$pull_request_id marked as broken, either the source (#$repository_source_id) or " .
                    "destination repository (#$repository_destination_id) is not available anymore"
                );
                return;
            }

            try {
                $this->updater->updatePullRequestReference(
                    $pull_request_without_git_ref,
                    GitExec::buildFromRepository($repository_source),
                    GitExec::buildFromRepository($repository_destination),
                    $repository_destination
                );
                $this->logger->debug("Git reference successfully created for PR #$pull_request_id");
            } catch (\Git_Command_Exception $ex) {
                $this->logger->error("PR #$pull_request_id marked as broken: " . $ex->getMessage());
            } catch (GitPullRequestReferenceNotFoundException $ex) {
                $this->logger->error('Incoherent state found, did you run forgeupgrade?', ['exception' => $ex]);
            }
        }
    }
}
