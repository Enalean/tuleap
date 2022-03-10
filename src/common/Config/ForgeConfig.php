<?php
/*
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
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

use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigValueDefaultValueAttributeProvider;
use Tuleap\Config\ConfigValueEnvironmentProvider;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

class ForgeConfig
{
    public const AUTH_TYPE_LDAP      = 'ldap';
    public const FEATURE_FLAG_PREFIX = 'feature_flag_';

    /**
     * Hold the configuration variables
     */
    protected static array $conf_stack = [0 => []];

    private static ?ConfigDao $config_dao = null;

    /**
     * Load the configuration variables into the current stack
     *
     * @access protected for testing purpose
     */
    protected static function load(ConfigValueProvider $value_provider)
    {
        // Store in the stack the local scope...
        self::$conf_stack[0] = array_merge(self::$conf_stack[0], $value_provider->getVariables());
    }

    public static function loadInSequence(): void
    {
        self::loadLocalInc();
        self::loadDatabaseConfig();
        self::loadFromDatabase();
        self::loadFromFile(self::get('redis_config_file'));
    }

    public static function loadDatabaseConfig(): void
    {
        self::loadDatabaseDefaultValues();
        self::loadDatabaseInc();
        self::loadDatabaseParametersFromEnvironment();
    }

    private static function loadLocalInc(): void
    {
        self::loadFromFile(__DIR__ . '/../../etc/local.inc.dist');
        $local_inc_file_path = (new Config_LocalIncFinder())->getLocalIncPath();
        self::loadFromFile($local_inc_file_path);
    }

    private static function loadDatabaseDefaultValues(): void
    {
        self::load(new ConfigValueDefaultValueAttributeProvider(\Tuleap\DB\DBConfig::class));
    }

    private static function loadDatabaseInc(): void
    {
        $database_config_file = self::get('db_config_file');
        if (is_file($database_config_file)) {
            self::loadFromFile($database_config_file);
        } elseif (is_file('/etc/tuleap/conf/database.inc')) {
            self::loadFromFile('/etc/tuleap/conf/database.inc');
        }
    }

    private static function loadDatabaseParametersFromEnvironment(): void
    {
        self::load(new ConfigValueEnvironmentProvider(\Tuleap\DB\DBConfig::class));
    }

    public static function loadFromFile($file): void
    {
        self::load(new ConfigValueFileProvider($file));
    }

    private static function loadFromDatabase(): void
    {
        self::load(new ConfigValueDatabaseProvider(self::getDatabaseConfigDao()));
    }

    private static function getDatabaseConfigDao(): ConfigDao
    {
        if (self::$config_dao) {
            return self::$config_dao;
        }
        return new ConfigDao();
    }

    public static function setDatabaseConfigDao(ConfigDao $config_dao): void
    {
        self::$config_dao = $config_dao;
    }

    public static function getAll(): Generator
    {
        foreach (self::$conf_stack[0] as $name => $value) {
            yield $name => $value;
        }
    }

    /**
     * Get the $name configuration variable
     *
     * @param $name    string the variable name
     * @param $default mixed  the value to return if the variable is not set in the configuration
     *
     */
    public static function get(string $name, mixed $default = false): mixed
    {
        if (self::exists($name)) {
            return self::$conf_stack[0][$name];
        }
        return $default;
    }

    public static function getInt(string $name, int $default = 0): int
    {
        if (self::exists($name)) {
            return (int) self::$conf_stack[0][$name];
        }
        return $default;
    }

    public static function getStringAsBool(string $name): bool
    {
        return self::$conf_stack[0][$name] === \Tuleap\Config\ConfigKeyLegacyBool::TRUE;
    }

    /**
     * @throws \Tuleap\Cryptography\Exception\InvalidCiphertextException
     * @throws \Tuleap\Config\UnknownConfigKeyException
     * @throws \Tuleap\Cryptography\Exception\CannotPerformIOOperationException
     */
    public static function getSecretAsClearText(string $name): ConcealedString
    {
        if (! self::exists($name)) {
            throw new \Tuleap\Config\UnknownConfigKeyException($name);
        }

        if (self::get($name) === '') {
            return new ConcealedString('');
        }

        return self::decryptValue(self::get($name));
    }


    public static function exists($name): bool
    {
        return isset(self::$conf_stack[0][$name]);
    }

    public static function getSuperPublicProjectsFromRestrictedFile()
    {
        $filename = $GLOBALS['Language']->getContent('include/restricted_user_permissions', 'en_US');
        if (! $filename) {
            return [];
        }

        $public_projects = [];
        include($filename);

        return $public_projects;
    }

    /**
     * Dump the content of the config for debugging purpose
     */
    public static function dump(): void
    {
        var_export(self::$conf_stack[0]);
    }

    /**
     * Store and clear the current stack. Only useful for testing purpose. DON'T USE IT IN PRODUCTION
     * @see ConfigTest::setUp() for details
     */
    public static function store(): void
    {
        array_unshift(self::$conf_stack, []);
        if (! count(self::$conf_stack)) {
            trigger_error('Config registry lost');
        }
    }

    /**
     * Restore the previous stack. Only useful for testing purpose. DON'T USE IT IN PRODUCTION
     * @see ConfigTest::tearDown() for details
     */
    public static function restore(): void
    {
        if (count(self::$conf_stack) > 1) {
            array_shift(self::$conf_stack);
        }
    }

    /**
     * @template T
     *
     * @psalm-param callable():T $fn
     *
     * @throws Throwable
     *
     * @psalm-return T
     */
    public static function wrapWithCleanConfig(callable $fn): mixed
    {
        self::store();
        $result = $fn();
        self::restore();
        return $result;
    }

    /**
     * Set a configuration value. Only useful for testing purpose. DON'T USE IT IN PRODUCTION
     */
    public static function set(string $name, mixed $value): void
    {
        self::$conf_stack[0][$name] = $value;
    }

    public static function areAnonymousAllowed()
    {
        return self::get(ForgeAccess::CONFIG) === ForgeAccess::ANONYMOUS;
    }

    public static function areRestrictedUsersAllowed()
    {
        return self::get(ForgeAccess::CONFIG) === ForgeAccess::RESTRICTED;
    }

    public static function getApplicationUserLogin()
    {
        return self::get('sys_http_user');
    }

    public static function areUnixGroupsAvailableOnSystem()
    {
        return trim(self::get('grpdir_prefix')) !== '';
    }

    public static function areUnixUsersAvailableOnSystem()
    {
        return trim(self::get('homedir_prefix')) !== '';
    }

    public static function getCacheDir()
    {
        return self::get('codendi_cache_dir');
    }

    public static function getFeatureFlag(string $key): mixed
    {
        return self::get(self::FEATURE_FLAG_PREFIX . $key);
    }

    public static function setFeatureFlag(string $name, mixed $value): void
    {
        self::set(self::FEATURE_FLAG_PREFIX . $name, $value);
    }

    public static function encryptValue(ConcealedString $value): string
    {
        return \sodium_bin2base64(
            SymmetricCrypto::encrypt($value, (new KeyFactory())->getEncryptionKey()),
            SODIUM_BASE64_VARIANT_ORIGINAL
        );
    }

    private static function decryptValue(string $value): ConcealedString
    {
        return SymmetricCrypto::decrypt(
            \sodium_base642bin($value, SODIUM_BASE64_VARIANT_ORIGINAL),
            (new KeyFactory())->getEncryptionKey(),
        );
    }
}
