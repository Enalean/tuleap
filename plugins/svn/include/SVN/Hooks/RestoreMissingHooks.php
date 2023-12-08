<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\SVN\Hooks;

use ForgeConfig;
use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\SVNCore\Repository;

final class RestoreMissingHooks
{
    public function __construct(
        private readonly MissingHooksPathsRetriever $missing_hooks_paths_retriever,
        private readonly LoggerInterface $logger,
        private readonly \BackendSVN $backend_svn,
    ) {
    }

    public function restoreAllMissingHooks(): void
    {
        $this->missing_hooks_paths_retriever->retrieveAllMissingHooksPaths()->match(
            /**
             * @param Repository[] $repositories
             */
            function (array $repositories) {
                foreach ($repositories as $repository) {
                    if (
                        $this->backend_svn->updateHooks(
                            $repository->getProject(),
                            $repository->getSystemPath(),
                            true,
                            ForgeConfig::get('tuleap_dir') . '/plugins/svn/bin/',
                            'svn_post_commit.php',
                            ForgeConfig::get('tuleap_dir') . '/src/utils/php-launcher.sh',
                            'svn_pre_commit.php'
                        )
                    ) {
                        $this->logger->warning(
                            "Hooks for SVN repository `" . $repository->getName() . "` in project `" . $repository->getProject()->getPublicName() . "` were missing and have been restored."
                        );
                    } else {
                        $this->logger->warning(
                            "Error while resetting hooks for SVN repository `" . $repository->getName() . "` in project " . $repository->getProject()->getPublicName()
                        );
                    }
                }
                return Result::ok(null);
            },
            fn (Fault $fault) => Fault::writeToLogger($fault, $this->logger)
        );
    }
}
