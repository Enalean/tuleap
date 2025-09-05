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

use Tuleap\Cryptography\ConcealedString;

final class LocalMeilisearchServer implements IProvideCurrentKeyForLocalServer
{
    public const ENV_KEY_PREFIX = 'MEILI_MASTER_KEY=';

    public function __construct(private string $root_dir = '/')
    {
    }

    private function isLocalServerInstalled(): bool
    {
        return is_file($this->root_dir . 'usr/bin/tuleap-meilisearch');
    }

    public function getExpectedMasterKeyEnvFilePath(): ?string
    {
        if (! $this->isLocalServerInstalled()) {
            return null;
        }

        return $this->root_dir . 'var/lib/tuleap/fts_meilisearch_server/meilisearch-master-key.env';
    }

    #[\Override]
    public function getCurrentKey(): ?ConcealedString
    {
        $key_path = $this->getExpectedMasterKeyEnvFilePath();
        if ($key_path === null) {
            return null;
        }

        $file_content = @file_get_contents($key_path);

        if ($file_content === false || ! str_starts_with($file_content, self::ENV_KEY_PREFIX)) {
            return null;
        }

        $key = new ConcealedString(substr($file_content, strlen(self::ENV_KEY_PREFIX)));
        sodium_memzero($file_content);

        return $key;
    }
}
