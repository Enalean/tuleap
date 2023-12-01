<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Config;

use ConfigValueDatabaseProvider;
use ConfigValueProvider;
use ForgeAccess;
use ForgeConfig;
use org\bovigo\vfs\vfsStream;
use Tuleap\DB\DBConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Mail\Transport\MailTransportBuilder;
use Tuleap\ServerHostname;

/**
 * @covers \Tuleap\Config\ConfigValueEnvironmentProvider
 */
class ForgeConfigTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    protected function tearDown(): void
    {
        putenv('TULEAP_LOCAL_INC');
        putenv('TULEAP_SYS_DBHOST');
        putenv('TULEAP_SYS_DEFAULT_DOMAIN');
        putenv('TULEAP_SYS_EMAIL_ADMIN');
        parent::tearDown();
    }

    /**
     * @dataProvider getDefaultSequenceProvider
     */
    public function testLoadInSequence(array $local_inc, array $database, array $environment, callable $tests): void
    {
        // Prepare local.inc
        $content = '<?php' . PHP_EOL;
        foreach ($local_inc as $key => $value) {
            $content .= sprintf('$%s = \'%s\';%s', $key, $value, PHP_EOL);
        }
        $root = vfsStream::setup()->url();
        file_put_contents($root . '/local.inc', $content);
        putenv('TULEAP_LOCAL_INC=' . $root . '/local.inc');

        // Prepare database
        $dao = $this->createMock(ConfigDao::class);
        $dao->method('searchAll')->willReturn($database);
        ForgeConfig::setDatabaseConfigDao($dao);

        // Prepare environment
        foreach ($environment as $key => $value) {
            putenv("$key=$value");
        }

        $tests();

        // Clean-up environment
        foreach ($environment as $key => $value) {
            putenv("$key");
        }
    }

    public static function getDefaultSequenceProvider(): iterable
    {
        return [
            'The value comes from attributes' => [
                'local_inc' => [],
                'database' => [],
                'environment' => [],
                'tests' => function () {
                    ForgeConfig::loadInSequence();
                    self::assertEquals('Tuleap', ForgeConfig::get(ConfigurationVariables::ORG_NAME));
                },
            ],
            'The default value is in attributes and override in local.inc' => [
                'local_inc' => [
                    ConfigurationVariables::ORG_NAME => 'Acme Gmbh',
                ],
                'database' => [],
                'environment' => [],
                'tests' => function () {
                    ForgeConfig::loadInSequence();
                    self::assertEquals('Acme Gmbh', ForgeConfig::get(ConfigurationVariables::ORG_NAME));
                },
            ],
            'Default value is in local.inc.dist' => [
                'local_inc' => [],
                'database' => [],
                'environment' => [],
                'tests' => function () {
                    ForgeConfig::loadInSequence();
                    self::assertEquals('%sys_default_domain%', ForgeConfig::get('sys_default_domain'));
                },
            ],
            'Value is set in local.inc' => [
                'local_inc' => [
                    'sys_default_domain' => 'tuleap.example.com',
                ],
                'database' => [],
                'environment' => [],
                'tests' => function () {
                    ForgeConfig::loadInSequence();
                    self::assertEquals('tuleap.example.com', ForgeConfig::get('sys_default_domain'));
                },
            ],
            'Value is set in database' => [
                'local_inc' => [],
                'database' => [
                    [
                        'name'  => \ProjectManager::CONFIG_PROJECT_APPROVAL,
                        'value' => '1',
                    ],
                ],
                'environment' => [],
                'tests' => function () {
                    ForgeConfig::loadInSequence();
                    self::assertSame('1', ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL));
                },
            ],
            'Database value comes from database.inc' => [
                'local_inc' => [
                    'db_config_file' =>  __DIR__ . '/_fixtures/sequence/database.inc',
                ],
                'database' => [],
                'environment' => [],
                'tests' => function () {
                    ForgeConfig::loadInSequence();
                    self::assertEquals('foo', ForgeConfig::get('sys_dbhost'));
                },
            ],
            'Environment takes precedence on database.inc' => [
                'local_inc' => [
                    'db_config_file' =>  __DIR__ . '/_fixtures/sequence/database.inc',
                ],
                'database' => [],
                'environment' => [
                    'TULEAP_SYS_DBHOST' => 'db.example.com',
                ],
                'tests' =>  function () {
                    ForgeConfig::loadInSequence();
                    self::assertEquals('db.example.com', ForgeConfig::get('sys_dbhost'));
                },
            ],
            'Plugin define a default value with attributes' => [
                'local_inc' => [],
                'database' => [],
                'environment' => [],
                'tests' => function () {
                    ForgeConfig::loadInSequence();

                    \ForgeConfig::loadPluginsDefaultValues(['some_key' => 'foo_bar']);
                    self::assertEquals('foo_bar', \ForgeConfig::get('some_key'));
                },
            ],
            'Plugin value loaded from database has precedence over attributes' => [
                'local_inc' => [],
                'database' => [
                    [
                        'name'  => 'some_key',
                        'value' => 'set_in_db',
                    ],
                ],
                'environment' => [],
                'tests' => function () {
                    ForgeConfig::loadInSequence();

                    \ForgeConfig::loadPluginsDefaultValues(['some_key' => 'foo_bar']);
                    self::assertEquals('set_in_db', \ForgeConfig::get('some_key'));
                },
            ],
        ];
    }

    public function testUsage(): void
    {
        $this->assertFalse(ForgeConfig::get('toto'));
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertEquals(ForgeConfig::get('toto'), 66);
        $this->assertFalse(ForgeConfig::get('titi')); //not defined should return false
    }

    public function testDefault(): void
    {
        $this->assertEquals(
            ForgeConfig::get('toto', 99),
            99
        ); //not defined should return default value given in parameter
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertEquals(
            ForgeConfig::get('toto', 99),
            66
        ); //now it is defined. Should NOT return default value given in parameter
    }

    public function testMultipleFiles(): void
    {
        // Unitialized
        $this->assertSame(ForgeConfig::get('toto'), false);
        $this->assertSame(ForgeConfig::get('tutu'), false);
        $this->assertSame(ForgeConfig::get('tata'), false);

        // Load the first file
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertSame(ForgeConfig::get('toto'), 66);
        $this->assertSame(ForgeConfig::get('tutu'), 123);
        $this->assertSame(ForgeConfig::get('tata'), false);

        // Load the second one. Merge of the conf
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/other_file.inc.dist');
        $this->assertSame(ForgeConfig::get('toto'), 66);
        $this->assertSame(ForgeConfig::get('tutu'), 421);
        $this->assertSame(ForgeConfig::get('tata'), 456);
    }

    public function testDump(): void
    {
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        ob_start();
        ForgeConfig::dump();
        $dump = ob_get_clean();
        $this->assertEquals($dump, var_export(['toto' => 66, 'tutu' => 123], true));
    }

    public function testItDoesntEmitAnyNoticesOrWarningsWhenThereAreTwoRestoresAndOneLoad(): void
    {
        ForgeConfig::restore();
        ForgeConfig::restore();
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertTrue(true);
    }

    public function testItLoadsFromDatabase(): void
    {
        $dao = $this->createMock(ConfigDao::class);
        $dao->method('searchAll')->willReturn([['name' => 'a_var', 'value' => 'its_value']]);

        (new class extends ForgeConfig {
            public static function load(ConfigValueProvider $value_provider): void
            {
                parent::load($value_provider);
            }
        })::load(new ConfigValueDatabaseProvider($dao));

        $this->assertEquals('its_value', ForgeConfig::get('a_var'));
    }

    public function testItReturnsTrueIfAccessModeIsAnonymous(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $this->assertTrue(ForgeConfig::areAnonymousAllowed());
    }

    public function testItReturnsFalseIfAccessModeIsRegular(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->assertFalse(ForgeConfig::areAnonymousAllowed());
    }

    public function testItReturnsFalseIfAccessModeIsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->assertFalse(ForgeConfig::areAnonymousAllowed());
    }

    public function testFeatureFlag(): void
    {
        ForgeConfig::set('feature_flag_list_picker', true);

        self::assertTrue(ForgeConfig::getFeatureFlag('list_picker'));
        self::assertFalse(ForgeConfig::getFeatureFlag('another_flag'));

        ForgeConfig::clearFeatureFlag('list_picker');
        self::assertFalse(ForgeConfig::getFeatureFlag('list_picker'));
    }

    public function testItReturnsEmptyArrayIfRestrictedUserFileIsTheDefaultOne(): void
    {
        $default_file = __DIR__ . '/../../../../site-content/en_US/include/restricted_user_permissions.txt';

        $GLOBALS['Language']
            ->method('getContent')
            ->with('include/restricted_user_permissions', 'en_US')
            ->willReturn($default_file);

        $this->assertEquals(ForgeConfig::getSuperPublicProjectsFromRestrictedFile(), []);
    }

    public function testItReturnsArrayOfProjectIdsDefinedInRestrictedUserFile(): void
    {
        $customised_file = __DIR__ . '/_fixtures/restricted_user_permissions.txt';

        $GLOBALS['Language']
            ->method('getContent')
            ->with('include/restricted_user_permissions', 'en_US')
            ->willReturn($customised_file);

        $this->assertEquals(ForgeConfig::getSuperPublicProjectsFromRestrictedFile(), [123, 456]);
    }

    public function testItDoesNotStorePublicProjectsInTheStorage(): void
    {
        $customised_file = __DIR__ . '/_fixtures/restricted_user_permissions.txt';

        $GLOBALS['Language']
            ->method('getContent')
            ->with('include/restricted_user_permissions', 'en_US')
            ->willReturn($customised_file);

        ForgeConfig::getSuperPublicProjectsFromRestrictedFile();

        $this->assertFalse(ForgeConfig::get('public_projects'));
    }

    public function testEncryptedSecretIsRevealed(): void
    {
        ForgeConfig::set('sys_custom_dir', vfsStream::setup('root', null, ['conf' => []])->url());
        ForgeConfig::set(
            MailTransportBuilder::RELAYHOST_SMTP_PASSWORD,
            ForgeConfig::encryptValue(new \Tuleap\Cryptography\ConcealedString('a very good secret')),
        );

        self::assertEquals('a very good secret', ForgeConfig::getSecretAsClearText(MailTransportBuilder::RELAYHOST_SMTP_PASSWORD));
    }

    /**
     * @dataProvider getSetupSequenceProvider
     */
    public function testGetSetupSequence(string $expected, string $variable, string $fqdn, array $env): void
    {
        foreach ($env as $key => $value) {
            putenv("$key=$value");
        }
        ForgeConfig::loadForInitialSetup($fqdn);
        self::assertEquals($expected, ForgeConfig::get($variable));
        foreach ($env as $key => $value) {
            putenv($key);
        }
    }

    public static function getSetupSequenceProvider(): iterable
    {
        return [
            'Hostname is defined by the provided fqdn' => [
                'expected' => 'tuleap.example.com',
                'variable' => ServerHostname::DEFAULT_DOMAIN,
                'fqdn' => 'tuleap.example.com',
                'env' => [],
            ],
            'Hostname is defined by environment variable fqdn' => [
                'expected' => 'another.example.com',
                'variable' => ServerHostname::DEFAULT_DOMAIN,
                'fqdn' => 'tuleap.example.com',
                'env' => [
                    'TULEAP_SYS_DEFAULT_DOMAIN' => 'another.example.com',
                ],
            ],
            'Admin email is defined by the provided fqdn' => [
                'expected' => 'codendi-admin@tuleap.example.com',
                'variable' => ConfigurationVariables::EMAIL_ADMIN,
                'fqdn' => 'tuleap.example.com',
                'env' => [],
            ],
            'Admin email is defined by environment variable' => [
                'expected' => 'foo@example.com',
                'variable' => ConfigurationVariables::EMAIL_ADMIN,
                'fqdn' => 'tuleap.example.com',
                'env' => [
                    'TULEAP_SYS_EMAIL_ADMIN' => 'foo@example.com',
                ],
            ],
            'DB host name is the default one' => [
                'expected' => 'localhost',
                'variable' => DBConfig::CONF_HOST,
                'fqdn' => 'tuleap.example.com',
                'env' => [],
            ],
            'DB host name is defined by environment variable (was testLoadDatabaseConfigFromEnvironmentWithEnv)' => [
                'expected' => 'db.example.com',
                'variable' => DBConfig::CONF_HOST,
                'fqdn' => 'tuleap.example.com',
                'env' => [
                    'TULEAP_SYS_DBHOST' => 'db.example.com',
                ],
            ],
        ];
    }

    public function testLoadDatabaseConfigDefaultValues(): void
    {
        ForgeConfig::loadForInitialSetup('tuleap.example.com');
        self::assertEquals('localhost', ForgeConfig::get('sys_dbhost'));
        self::assertEquals('tuleap', ForgeConfig::get(DBConfig::CONF_DBNAME));
        self::assertSame(3306, ForgeConfig::get(DBConfig::CONF_PORT));
        self::assertSame('0', ForgeConfig::get(DBConfig::CONF_ENABLE_SSL));
    }

    /**
     * @param int[] $expected
     * @dataProvider dataProviderArrayOfInt
     */
    public function testArrayOfInt(string $value, array $expected): void
    {
        ForgeConfig::setFeatureFlag('comma-separated', $value);

        self::assertSame($expected, ForgeConfig::getFeatureFlagArrayOfInt('comma-separated'));
    }

    public static function dataProviderArrayOfInt(): array
    {
        return [
            'Nothing' => ['', []],
            '0 means 0, not nothing' => ['0', [0]],
            'One value' => ['123', [123]],
            'Multiple values' => ['123,456,789', [123, 456, 789]],
            '0 among multiple values is ignored' => ['123,0,789', [123, 789]],
            'Multiple values with spaces' => ['123 , 456 , 789', [123, 456, 789]],
            'Non int are silently ignored' => ['123,whatever,456', [123, 456]],
            'Extra commas are ignored' => [',123,,456,,', [123, 456]],
        ];
    }
}
