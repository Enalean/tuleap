<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\Config\ConfigCannotBeModified;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\GetConfigKeys;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class ConfigResetCommandTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testCanResetASetting(): void
    {
        $event_dispatcher = EventDispatcherStub::withCallback(
            static function (GetConfigKeys $config_keys): GetConfigKeys {
                $class = new class {
                    #[ConfigKey('summary')]
                    #[ConfigKeyString('default')]
                    public const SETTING = 'setting';
                };

                $config_keys->addConfigClass($class::class);
                return $config_keys;
            }
        );

        \ForgeConfig::set('setting', 'value');
        $config_dao = $this->createMock(ConfigDao::class);

        $config_dao->expects(self::atLeastOnce())->method('delete');

        $exit_code = $this->executeCommand(new ConfigResetCommand($event_dispatcher, $config_dao));

        self::assertSame(0, $exit_code);
    }

    public function testDoesTryToResetASettingThatDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->executeCommand(new ConfigResetCommand(EventDispatcherStub::withIdentityCallback(), $this->createStub(ConfigDao::class)));
    }

    public function testDoesTryToResetASettingWithNoDefaultValue(): void
    {
        $event_dispatcher = EventDispatcherStub::withCallback(
            static function (GetConfigKeys $config_keys): GetConfigKeys {
                $class = new class {
                    #[ConfigKey('summary')]
                    public const SETTING = 'setting';
                };

                $config_keys->addConfigClass($class::class);
                return $config_keys;
            }
        );

        \ForgeConfig::set('setting', 'value');

        $this->expectException(InvalidArgumentException::class);
        $this->executeCommand(new ConfigResetCommand($event_dispatcher, $this->createStub(ConfigDao::class)));
    }

    public function testDoesTryToResetASettingThatCannotBeModified(): void
    {
        $event_dispatcher = EventDispatcherStub::withCallback(
            static function (GetConfigKeys $config_keys): GetConfigKeys {
                $class = new class {
                    #[ConfigKey('summary')]
                    #[ConfigKeyString('default')]
                    #[ConfigCannotBeModified]
                    public const SETTING = 'setting';
                };

                $config_keys->addConfigClass($class::class);
                return $config_keys;
            }
        );

        \ForgeConfig::set('setting', 'value');

        $this->expectException(InvalidArgumentException::class);
        $this->executeCommand(new ConfigResetCommand($event_dispatcher, $this->createStub(ConfigDao::class)));
    }

    private function executeCommand(ConfigResetCommand $command): int
    {
        return (new CommandTester($command))->execute(['key' => 'setting']);
    }
}
