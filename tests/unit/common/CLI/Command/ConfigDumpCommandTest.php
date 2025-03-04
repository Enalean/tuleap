<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\CLI\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigDumpCommandTest extends TestCase
{
    use ForgeConfigSandbox;

    #[\PHPUnit\Framework\Attributes\DataProvider('getTestData')]
    public function testVariablesInJsonFormat(array $variables_defined, string $expected_output): void
    {
        foreach ($variables_defined as $key => $value) {
            \ForgeConfig::set($key, $value);
        }

        $command_tester = new CommandTester(
            new ConfigDumpCommand(new \EventManager())
        );
        $command_tester->execute([
            'keys' => array_keys($variables_defined),
        ]);

        assertEquals(0, $command_tester->getStatusCode());
        assertEquals(
            $expected_output,
            $command_tester->getDisplay()
        );
    }

    public static function getTestData(): \Generator
    {
        yield 'one string variable' => [
            'variables_defined' => [
                'sys_dbhost' => 'localhost',
            ],
            'expected_output' =>  \json_encode(['sys_dbhost' => 'localhost']),
        ];

        yield 'one integer value' => [
            'variables_defined' => [
                'sys_dbport' => 3306,
            ],
            'expected_output' => \json_encode(['sys_dbport' => 3306]),
        ];

        yield 'two variables' => [
            'variables_defined' => [
                'sys_dbhost' => 'localhost',
                'sys_dbport' => 3306,
            ],
            'expected_output' => \json_encode(['sys_dbhost' => 'localhost', 'sys_dbport' => 3306]),
        ];

        yield 'boolean value encoded as integer' => [
            'variables_defined' => [
                'sys_enablessl' => 0,
            ],
            'expected_output' => \json_encode(['sys_enablessl' => 0]),
        ];
    }

    public function testRequestingAnInvalidVariableIsIgnoredBecauseSomeVariablesMightNotBePresentForHistoricalReasons(): void
    {
        \ForgeConfig::set('sys_dbhost', 'db');
        $command_tester = new CommandTester(
            new ConfigDumpCommand(new \EventManager())
        );
        $command_tester->execute(
            [
                'keys'     => ['sys_dbhost', 'sys_dbport'],
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        assertEquals(0, $command_tester->getStatusCode());
        assertEmpty($command_tester->getErrorOutput());
        assertEquals(['sys_dbhost' => 'db'], \json_decode($command_tester->getDisplay(), true, JSON_THROW_ON_ERROR));
    }

    public function testGetAllVariables(): void
    {
        \ForgeConfig::set('sys_dbhost', 'db');
        \ForgeConfig::set('sys_dbport', 3306);
        \ForgeConfig::set('sys_enablessl', 0);


        $command_tester = new CommandTester(
            new ConfigDumpCommand(new \EventManager())
        );
        $command_tester->execute([]);

        assertEquals(0, $command_tester->getStatusCode());
        assertEquals(['sys_dbhost' => 'db', 'sys_dbport' => 3306, 'sys_enablessl' => 0], \json_decode($command_tester->getDisplay(), true, JSON_THROW_ON_ERROR));
    }

    public function testLdapPluginShouldHaveAWayToAddItsVariablesIntoForgeConfigPriorToDump(): void
    {
        \ForgeConfig::set('sys_dbhost', 'db');

        $event_manager = new \EventManager();
        $event_manager->addListener(ConfigDumpEvent::NAME, null, fn (ConfigDumpEvent $event) => \ForgeConfig::set('sys_ldap_server', 'foo-bar'), false);

        $command_tester = new CommandTester(
            new ConfigDumpCommand($event_manager)
        );
        $command_tester->execute([]);

        assertEquals(0, $command_tester->getStatusCode());
        assertEquals(['sys_dbhost' => 'db', 'sys_ldap_server' => 'foo-bar'], \json_decode($command_tester->getDisplay(), true, JSON_THROW_ON_ERROR));
    }
}
