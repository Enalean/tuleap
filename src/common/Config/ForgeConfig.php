<?php
/**
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
use Tuleap\Config\ConfigKeyLegacyBool;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Config\ConfigValueDefaultValueAttributeProvider;
use Tuleap\Config\ConfigValueEnvironmentProvider;
use Tuleap\Config\GetConfigKeys;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Mail\Transport\MailTransportBuilder;
use Tuleap\ServerHostname;

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
        self::loadCoreDefaultsFromAttributes();
        self::loadLocalInc();
        self::loadDatabaseConfig();
        self::loadFromDatabase();
        self::loadFromFile(self::get('redis_config_file'));
    }

    /**
     * @param array<string, mixed> $variables
     */
    public static function loadPluginsDefaultValues(array $variables): void
    {
        foreach ($variables as $key => $value) {
            if (self::exists($key)) {
                continue;
            }
            self::set($key, $value);
        }
    }

    public static function loadForInitialSetup(string $fqdn): void
    {
        self::loadCoreDefaultsFromAttributes();
        self::loadLocalInc();
        self::initDefaultValues($fqdn);
        self::load(new ConfigValueEnvironmentProvider(ServerHostname::class, ConfigurationVariables::class, MailTransportBuilder::class));
        self::loadDatabaseConfig();
    }

    /**
     * Default values for a new platform
     *
     * There are two kind of default values:
     * - Variables with historical placeholders in `local.inc.dist`
     * - Variables that have a default value that should stay for existing platforms (to avoid regression) but that
     *   we want to push new defaults.
     */
    private static function initDefaultValues(string $fqdn): void
    {
        self::set(ServerHostname::DEFAULT_DOMAIN, $fqdn);
        self::set(ConfigurationVariables::EMAIL_ADMIN, 'codendi-admin@' . $fqdn);
        self::set(ConfigurationVariables::EMAIL_CONTACT, 'codendi-contact@' . $fqdn);
        self::set(ConfigurationVariables::NOREPLY, sprintf('"Tuleap" <noreply@%s>', $fqdn));
        self::setNewDefault(ConfigurationVariables::MAIL_SECURE_MODE, ConfigKeyLegacyBool::FALSE);
        self::setNewDefault(ConfigurationVariables::DISABLE_SUBDOMAINS, ConfigKeyLegacyBool::TRUE);
    }

    private static function setNewDefault(string $key, mixed $value): void
    {
        self::set($key, $value);
    }

    private static function loadDatabaseConfig(): void
    {
        self::loadDatabaseInc();
        self::loadDatabaseParametersFromEnvironment();
    }

    private static function loadCoreDefaultsFromAttributes(): void
    {
        self::load(new ConfigValueDefaultValueAttributeProvider(...GetConfigKeys::CORE_CLASSES_WITH_CONFIG_KEYS));
    }

    private static function loadLocalInc(): void
    {
        self::loadFromFile(__DIR__ . '/../../etc/local.inc.dist');
        self::loadFromFile((new Config_LocalIncFinder())->getLocalIncPath());
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

    /**
     * @return int[]
     */
    private static function getArrayOfInt(string $name): array
    {
        if (! self::exists($name)) {
            return [];
        }

        if (self::$conf_stack[0][$name] === '0') {
            return [0];
        }

        return array_values(
            array_filter(
                array_map(
                    static fn(string $value): int => (int) $value,
                    explode(',', self::$conf_stack[0][$name]),
                ),
            ),
        );
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

    public static function areRestrictedUsersAllowed(): bool
    {
        return self::get(ForgeAccess::CONFIG) === ForgeAccess::RESTRICTED;
    }

    public static function getApplicationUserLogin()
    {
        return self::get('sys_http_user');
    }

    public static function getCacheDir()
    {
        return self::get('codendi_cache_dir');
    }

    public static function getFeatureFlag(string $key): mixed
    {
        return self::get(self::FEATURE_FLAG_PREFIX . $key);
    }

    /**
     * @return int[]
     */
    public static function getFeatureFlagArrayOfInt(string $key): array
    {
        return self::getArrayOfInt(self::FEATURE_FLAG_PREFIX . $key);
    }

    public static function setFeatureFlag(string $name, mixed $value): void
    {
        self::set(self::FEATURE_FLAG_PREFIX . $name, $value);
    }

    public static function clearFeatureFlag(string $name): void
    {
        unset(self::$conf_stack[0][self::FEATURE_FLAG_PREFIX . $name]);
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
