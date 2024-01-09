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

namespace Tuleap\MediawikiStandalone\Configuration;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyHelp;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\ConfigKeyValueValidator;
use Tuleap\Cryptography\ConcealedString;

/**
 * @psalm-immutable
 */
#[ConfigKeyCategory('MediaWiki')]
final class LocalSettingsRepresentation
{
    #[ConfigKey('Shared database that centralize all MediaWiki tables')]
    #[ConfigKeyHelp(<<<EOT
    This configuration key should be used when system administrator want
    to restrict the number of database used. It's also useful in a context
    where it's not possible to create database at will.

    The value that must be provided is the name of the database where all
    MediaWiki instances tables will created (sharded by a prefix).
    EOT)]
    #[ConfigKeyString(null)]
    #[ConfigKeyValueValidator(MediaWikiCentralDatabaseParameterValidator::class)]
    public const CONFIG_CENTRAL_DATABASE = 'mediawiki_central_database';

    public const MEDIAWIKI_PHP_CLI = '/opt/remi/php82/root/usr/bin/php';

    public string $php_cli_path             = self::MEDIAWIKI_PHP_CLI;
    public string $supported_languages_json = '';

    public function __construct(
        public ConcealedString $pre_shared_key,
        public string $https_url,
        public string $oauth2_client_id,
        public ConcealedString $oauth2_client_secret,
        string $forge_supported_languages,
        public ?string $central_database,
    ) {
        $supported_languages = [];
        foreach (explode(',', $forge_supported_languages) as $supported_language) {
            $supported_languages[] = strtolower(trim(explode('_', $supported_language)[0]));
        }
        $this->supported_languages_json = json_encode(array_flip($supported_languages), JSON_THROW_ON_ERROR);
    }
}
