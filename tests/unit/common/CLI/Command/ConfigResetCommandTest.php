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
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigKeyMetadata;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\Config\ConfigKeyMetadataBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Config\KeyMetadataProviderStub;

final class ConfigResetCommandTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testCanResetASetting(): void
    {
        $config_keys = KeyMetadataProviderStub::buildWithMetadata([
            'setting' => $this->getMetadataWithDefaultValue(),
        ]);

        \ForgeConfig::set('setting', 'value');
        $config_dao = $this->createMock(ConfigDao::class);

        $config_dao->expects(self::atLeastOnce())->method('delete');

        $exit_code = $this->executeCommand(new ConfigResetCommand($config_keys, $config_dao));

        self::assertSame(0, $exit_code);
    }

    public function testDoesTryToResetASettingThatDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->executeCommand(new ConfigResetCommand(KeyMetadataProviderStub::buildWithMetadata([]), $this->createStub(ConfigDao::class)));
    }

    public function testDoesTryToResetASettingWithNoDefaultValue(): void
    {
        $config_keys = KeyMetadataProviderStub::buildWithMetadata([
            'setting' => $this->getMetadataWithoutDefaultValue(),
        ]);

        \ForgeConfig::set('setting', 'value');

        $this->expectException(InvalidArgumentException::class);
        $this->executeCommand(new ConfigResetCommand($config_keys, $this->createStub(ConfigDao::class)));
    }

    public function testDoesTryToResetASettingThatCannotBeModified(): void
    {
        $config_keys = KeyMetadataProviderStub::buildWithMetadata([
            'setting' => $this->getMetadataNotModifiable(),
        ]);

        \ForgeConfig::set('setting', 'value');

        $this->expectException(InvalidArgumentException::class);
        $this->executeCommand(new ConfigResetCommand($config_keys, $this->createStub(ConfigDao::class)));
    }

    private function executeCommand(ConfigResetCommand $command): int
    {
        return (new CommandTester($command))->execute(['key' => 'setting']);
    }

    private function getMetadataWithDefaultValue(): ConfigKeyMetadata
    {
        return ConfigKeyMetadataBuilder::aModifiableMetadata()->withDefaultValue()->build();
    }

    private function getMetadataWithoutDefaultValue(): ConfigKeyMetadata
    {
        return ConfigKeyMetadataBuilder::aModifiableMetadata()->build();
    }

    private function getMetadataNotModifiable(): ConfigKeyMetadata
    {
        return ConfigKeyMetadataBuilder::aNonModifiableMetadata()->withDefaultValue()->build();
    }
}
