<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Config;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class ConfigSetTest extends TestCase
{
    public function testExceptionIfKeyDoesNotExist(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::never())
            ->method('save');

        $config_set = new ConfigSet(EventDispatcherStub::withIdentityCallback(), $config_dao);

        $this->expectException(UnknownConfigKeyException::class);
        $config_set->set('unknown', 'value');
    }

    public function testExceptionIfKeyIsNotModifiable(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::never())
            ->method('save');

        $class           = new class {
            #[ConfigKey('summary')]
            #[ConfigCannotBeModified]
            public const SOME_STUFF = 'foo';
        };
        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        $config_set = new ConfigSet(EventDispatcherStub::withCallback(static fn() => $get_config_keys), $config_dao);

        $this->expectException(InvalidConfigKeyException::class);
        $config_set->set('foo', 'value');
    }

    public function testSaveConfigKeyValue(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::once())
            ->method('save')
            ->with('foo', 'value');

        $class           = new class {
            #[ConfigKey('summary')]
            public const SOME_STUFF = 'foo';
        };
        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        $config_set = new ConfigSet(EventDispatcherStub::withCallback(static fn() => $get_config_keys), $config_dao);
        $config_set->set('foo', 'value');
    }

    public function testExceptionIfValueIsNotValid(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::never())
            ->method('save');

        $class           = new class {
            #[ConfigKey('summary')]
            #[ConfigKeyValueValidator(ConfigSetTestValueValidator::class)]
            public const SOME_STUFF = 'foo';
        };
        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        $config_set = new ConfigSet(EventDispatcherStub::withCallback(static fn() => $get_config_keys), $config_dao);

        $this->expectException(InvalidConfigKeyValueException::class);
        $config_set->set('foo', 'value');
    }

    public function testExceptionIfSecretIsNotValid(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::never())
            ->method('save');

        $class           = new class {
            #[ConfigKey('summary')]
            #[ConfigKeySecret]
            #[ConfigKeySecretValidator(ConfigSetTestSecretValidator::class)]
            public const SOME_STUFF = 'foo';
        };
        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        $config_set = new ConfigSet(EventDispatcherStub::withCallback(static fn() => $get_config_keys), $config_dao);

        $this->expectException(InvalidConfigKeyValueException::class);
        $config_set->set('foo', 'value');
    }
}
