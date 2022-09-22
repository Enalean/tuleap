<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\FullTextSearchMeilisearch\Server;

use Psr\Log\LoggerInterface;

final class GenerateServerMasterKey
{
    public function __construct(
        private LocalMeilisearchServer $local_meilisearch_server,
        private LoggerInterface $logger,
    ) {
    }

    public function generateMasterKey(): void
    {
        $env_file_path = $this->local_meilisearch_server->getExpectedMasterKeyEnvFilePath();
        if ($env_file_path === null) {
            $this->logger->debug('No Meilisearch master key to write');
            return;
        }

        $this->logger->info('Write Meilisearch master key');

        $initial_umask = umask(0007);
        $is_success    = file_put_contents($env_file_path, LocalMeilisearchServer::ENV_KEY_PREFIX . sodium_bin2hex(random_bytes(32)));
        umask($initial_umask);

        if (! $is_success) {
            throw new \RuntimeException(sprintf('Could not write Meilisearch master key into %s', $env_file_path));
        }
    }
}
