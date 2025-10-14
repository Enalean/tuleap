<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\DB;

use Tuleap\Config\ConfigCannotBeModifiedYet;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\ConfigKeyLegacyBool;
use Tuleap\Config\ConfigKeySecret;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\ConfigKeyHelp;

#[ConfigKeyCategory('Database')]
final class DBConfig
{
    #[ConfigKey('Database server hostname or IP address')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyString('localhost')]
    public const string CONF_HOST = 'sys_dbhost';

    #[ConfigKey('Database server port')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyInt(self::DEFAULT_MYSQL_PORT)]
    public const string CONF_PORT = 'sys_dbport';

    #[ConfigKey('Database name')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyString(self::DEFAULT_MYSQL_TULEAP_DB_NAME)]
    public const string CONF_DBNAME = 'sys_dbname';

    #[ConfigKey('Database application user')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyString(self::DEFAULT_MYSQL_TULEAP_USER_NAME)]
    public const string CONF_DBUSER = 'sys_dbuser';

    #[ConfigKey('Database application user password')]
    #[ConfigKeySecret]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    public const string CONF_DBPASSWORD = 'sys_dbpasswd';

    #[ConfigKey('Database is accessed with TLS')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyLegacyBool(false)]
    #[ConfigKeyHelp(<<<EOT
    If set to '1' (one) connexions to DB are made through SSL.
    Note: Mysql server must be properly configured to accept SSL
    connection. Either with a grant for user or by forcing all
    connections to be in SSL with `require_secure_transport=ON`
    EOT)]
    public const string CONF_ENABLE_SSL = 'sys_enablessl';

    #[ConfigKey('Database TLS CA')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyString(self::DEFAULT_MYSQL_CA_FILE_PATH)]
    public const string CONF_SSL_CA = 'sys_db_ssl_ca';

    #[ConfigKey('Toggle verification of database certificate')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyLegacyBool(false)]
    public const string CONF_SSL_VERIFY_CERT = 'sys_db_ssl_verify_cert';

    #[ConfigKey('Adjust the maximum number of JOIN the mysql server can accept')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyInt(20)]
    public const string CONF_NB_MAX_JOIN = 'sys_server_join';

    public const int DEFAULT_MYSQL_PORT                = 3306;
    public const string DEFAULT_MYSQL_TULEAP_DB_NAME   = 'tuleap';
    public const string DEFAULT_MYSQL_TULEAP_USER_NAME = 'tuleapadm';
    public const string DEFAULT_MYSQL_CA_FILE_PATH     = '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem';

    public static function isSSLEnabled(): bool
    {
        return \ForgeConfig::get(self::CONF_ENABLE_SSL) === '1';
    }

    public static function isSSLVerifyCert(): bool
    {
        return \ForgeConfig::exists(self::CONF_SSL_VERIFY_CERT) && \ForgeConfig::get(self::CONF_SSL_VERIFY_CERT) === '1';
    }

    /**
     * @throws NoCaFileException
     */
    public static function getSSLCACertFile(): string
    {
        if (\ForgeConfig::exists(self::CONF_SSL_CA) && is_file(\ForgeConfig::get(self::CONF_SSL_CA))) {
            return \ForgeConfig::get(self::CONF_SSL_CA);
        }
        throw new NoCaFileException();
    }

    public static function getPDODSN(string $database_name): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s',
            \ForgeConfig::get(self::CONF_HOST),
            \ForgeConfig::get(self::CONF_PORT, self::DEFAULT_MYSQL_PORT),
            $database_name
        );
    }

    public static function isUsingDefaultPort(): bool
    {
        return (int) \ForgeConfig::get(self::CONF_PORT, self::DEFAULT_MYSQL_PORT) === self::DEFAULT_MYSQL_PORT;
    }
}
