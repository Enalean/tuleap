<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Config;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GetConfigKeysTest extends \Tuleap\Test\PHPUnit\TestCase
{
    protected function setUp(): void
    {
        \PluginManager::setInstance($this->createMock(\PluginManager::class));
    }

    protected function tearDown(): void
    {
        \PluginManager::clearInstance();
    }

    public function testGetKeysWithAnnotationsOnClasses(): void
    {
        $get_config_keys = new GetConfigKeys();

        $all_keys = $get_config_keys->getSortedKeysWithMetadata();
        self::assertArrayHasKey('sys_use_project_registration', $all_keys);
        self::assertEquals(
            new ConfigKeyMetadata(
                'Is project creation allowed to regular users (1) or not (0)',
                new ConfigKeyModifierDatabase(),
                false,
                false,
                true,
                null,
                null,
                null
            ),
            $all_keys['sys_use_project_registration'],
        );
    }

    public function testAttributeToPreventModification(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigCannotBeModified]
            public const SOME_STUFF = 'foo';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        self::assertInstanceOf(ConfigKeyNoModifier::class, $get_config_keys->getSortedKeysWithMetadata()['foo']->can_be_modified);
    }

    public function testCannotBeModifiedAttributeDependsOnConfigKey(): void
    {
        $class = new class {
            #[ConfigCannotBeModified]
            public const SOME_STUFF = 'foo';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        self::assertArrayNotHasKey('foo', $get_config_keys->getSortedKeysWithMetadata());
        self::assertArrayNotHasKey('', $get_config_keys->getSortedKeysWithMetadata());
    }

    public function testGetKeysThatCanBeModifiedListKeysThatCanBeModified(): void
    {
        $get_config_keys = new GetConfigKeys();

        self::assertContains('sys_use_project_registration', $get_config_keys->getKeysThatCanBeModifiedWithConfigSet());
    }

    public function testGetKeysThatCanBeModifiedDoesntListKeysThatCannotBeModified(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigCannotBeModified]
            public const SOME_STUFF = 'foo';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        self::assertNotContains('foo', $get_config_keys->getKeysThatCanBeModifiedWithConfigSet());
    }

    public function testCanBeModifiedWithAKeyThatCanBeModified(): void
    {
        $get_config_keys = new GetConfigKeys();

        self::assertInstanceOf(ConfigKeyModifierDatabase::class, $get_config_keys->getKeyMetadata('sys_use_project_registration')->can_be_modified);
    }

    public function testCanBeModifiedWithAKeyThatCannotBeModified(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigCannotBeModified]
            public const SOME_STUFF = 'foo';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        self::assertInstanceOf(ConfigKeyNoModifier::class, $get_config_keys->getKeyMetadata('foo')->can_be_modified);
    }

    public function testDetectsIfConfigHasADefaultValue(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigKeyString('')]
            public const SOME_STUFF = 'foo';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        self::assertTrue($get_config_keys->getKeyMetadata('foo')->has_default_value);
    }

    public function testConfigKeyHasCategory(): void
    {
        $class = new #[ConfigKeyCategory('bar')]
        class {
            #[ConfigKey('summary')]
            public const SOME_STUFF = 'foo';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        $keys = $get_config_keys->getSortedKeysWithMetadata();
        self::assertEquals('bar', $keys['foo']->category);
    }

    public function testConfigKeyWithValidator(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigKeyValueValidator(GetConfigKeysValueValidator::class)]
            public const SOME_STUFF = 'foo';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        $key_metadata = $get_config_keys->getKeyMetadata($class::SOME_STUFF);
        self::assertInstanceOf(GetConfigKeysValueValidator::class, $key_metadata->value_validator);
    }

    public function testConfigKeyHoldsSecret(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigKeySecret]
            public const SOME_STUFF = 'foo';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        $key_metadata = $get_config_keys->getKeyMetadata($class::SOME_STUFF);
        self::assertTrue($key_metadata->is_secret);
        self::assertNull($key_metadata->secret_validator);
    }

    public function testConfigKeyHoldsSecretWithDedicatedValidator(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigKeySecret]
            #[ConfigKeySecretValidator(GetConfigKeysSecretValidator::class)]
            public const SOME_STUFF = 'foo';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        $key_metadata = $get_config_keys->getKeyMetadata($class::SOME_STUFF);
        self::assertTrue($key_metadata->is_secret);
        self::assertInstanceOf(GetConfigKeysSecretValidator::class, $key_metadata->secret_validator);
    }

    public function testHiddenConfigKey(): void
    {
        $class = new class {
            #[ConfigKey('summary hidden key')]
            #[ConfigKeyHidden]
            public const HIDDEN_KEY = 'foo';

            #[ConfigKey('summary regular key')]
            public const SOME_STUFF = 'bar';
        };

        $get_config_keys = new GetConfigKeys();
        $get_config_keys->addConfigClass($class::class);

        $key_metadata = $get_config_keys->getKeyMetadata($class::HIDDEN_KEY);
        self::assertTrue($key_metadata->is_hidden);

        $key_metadata = $get_config_keys->getKeyMetadata($class::SOME_STUFF);
        self::assertFalse($key_metadata->is_hidden);
    }
}
