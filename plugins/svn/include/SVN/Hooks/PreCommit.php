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

namespace Tuleap\SVN\Hooks;

use Psr\Log\LoggerInterface;
use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVN\Commit\CommitInfoEnhancer;
use Tuleap\SVN\Commit\CommitMessageValidator;
use Tuleap\SVN\Commit\PathValidator;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVNCore\Repository;

class PreCommit
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Svnlook
     */
    private $svnlook;
    /**
     * @var PathValidator[]
     */
    private $path_validators;
    /**
     * @var CommitMessageValidator
     */
    private $commit_message_validator;

    public function __construct(
        Svnlook $svnlook,
        LoggerInterface $logger,
        CommitMessageValidator $commit_message_validator,
        PathValidator ...$path_validators,
    ) {
        $this->logger                   = $logger;
        $this->svnlook                  = $svnlook;
        $this->path_validators          = $path_validators;
        $this->commit_message_validator = $commit_message_validator;
    }

    public function assertCommitIsValid(Repository $repository, string $transaction): void
    {
        $commit_info_enhancer = new CommitInfoEnhancer($this->svnlook, new CommitInfo());
        $commit_info_enhancer->enhanceWithTransaction($repository, $transaction);

        $this->commit_message_validator->assertCommitMessageIsValid($repository, $commit_info_enhancer->getCommitInfo());

        $changed_paths = $this->svnlook->getTransactionPath($repository, $transaction);
        foreach ($changed_paths as $path) {
            foreach ($this->path_validators as $validator) {
                $validator->assertPathIsValid($repository, $transaction, $path);
            }
        }
        $this->logger->debug("Commit is allowed \o/");
    }
}
