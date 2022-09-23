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

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeySecret;
use Tuleap\Config\ConfigKeySecretValidator;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\ConfigKeyValueValidator;

#[ConfigKeyCategory('Full-text search Meilisearch')]
final class RemoteMeilisearchServerSettings
{
    #[ConfigKey('URL of the remote Meilisearch server')]
    #[ConfigKeyString]
    #[ConfigKeyValueValidator(MeilisearchServerURLValidator::class)]
    public const URL = 'fts_meilisearch_server_url';

    #[ConfigKey('API key to access the remote Meilisearch server')]
    #[ConfigKeySecret]
    #[ConfigKeySecretValidator(MeilisearchAPIKeyValidator::class)]
    public const API_KEY = 'fts_meilisearch_api_key';

    #[ConfigKey('Name of the index to use on the remote Meilisearch server')]
    #[ConfigKeyString('fts_tuleap')]
    #[ConfigKeyValueValidator(MeilisearchIndexNameValidator::class)]
    public const INDEX_NAME = 'fts_meilisearch_index_name';

    private function __construct()
    {
    }
}
