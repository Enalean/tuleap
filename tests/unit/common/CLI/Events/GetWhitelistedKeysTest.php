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

namespace Tuleap\CLI\Events;

use Tuleap\Config\ConfigCannotBeModified;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyMetadata;
use Tuleap\Config\ConfigKeySecret;

final class GetWhitelistedKeysTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetKeysWithAnnotationsOnClasses(): void
    {
        $get_whitelisted_keys = new GetWhitelistedKeys();

        $all_keys = $get_whitelisted_keys->getSortedKeysWithMetadata();
        self::assertArrayHasKey('sys_use_project_registration', $all_keys);
        self::assertEquals(
            new ConfigKeyMetadata('Is project creation allowed to regular users (1) or not (0)', true, false, null),
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

        $get_whitelisted_keys = new GetWhitelistedKeys();
        $get_whitelisted_keys->addConfigClass($class::class);

        self::assertFalse($get_whitelisted_keys->getSortedKeysWithMetadata()['foo']->can_be_modified);
    }

    public function testCannotBeModifiedAttributeDependsOnConfigKey(): void
    {
        $class = new class {
            #[ConfigCannotBeModified]
            public const SOME_STUFF = 'foo';
        };

        $get_whitelisted_keys = new GetWhitelistedKeys();
        $get_whitelisted_keys->addConfigClass($class::class);

        self::assertArrayNotHasKey('foo', $get_whitelisted_keys->getSortedKeysWithMetadata());
        self::assertArrayNotHasKey('', $get_whitelisted_keys->getSortedKeysWithMetadata());
    }

    public function testGetKeysThatCanBeModifiedListKeysThatCanBeModified(): void
    {
        $get_whitelisted_keys = new GetWhitelistedKeys();

        self::assertContains('sys_use_project_registration', $get_whitelisted_keys->getKeysThatCanBeModified());
    }

    public function testGetKeysThatCanBeModifiedDoesntListKeysThatCannotBeModified(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigCannotBeModified]
            public const SOME_STUFF = 'foo';
        };

        $get_whitelisted_keys = new GetWhitelistedKeys();
        $get_whitelisted_keys->addConfigClass($class::class);

        self::assertNotContains('foo', $get_whitelisted_keys->getKeysThatCanBeModified());
    }

    public function testCanBeModifiedWithAKeyThatCanBeModified(): void
    {
        $get_whitelisted_keys = new GetWhitelistedKeys();

        self::assertTrue($get_whitelisted_keys->canBeModified('sys_use_project_registration'));
    }

    public function testCanBeModifiedWithAKeyThatCannotBeModified(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigCannotBeModified]
            public const SOME_STUFF = 'foo';
        };

        $get_whitelisted_keys = new GetWhitelistedKeys();
        $get_whitelisted_keys->addConfigClass($class::class);

        self::assertFalse($get_whitelisted_keys->canBeModified('foo'));
    }

    public function testConfigKeyHasCategory(): void
    {
        // phpcs 3.6.0 detect an error with annotation bellow, ignore the error until phpcs is fixed
        // https://github.com/squizlabs/PHP_CodeSniffer/issues/3456
        // phpcs:ignore PSR12.Classes.ClassInstantiation.MissingParentheses
        $class = new #[ConfigKeyCategory('bar')] class {
            #[ConfigKey('summary')]
            public const SOME_STUFF = 'foo';
        };

        $get_whitelisted_keys = new GetWhitelistedKeys();
        $get_whitelisted_keys->addConfigClass($class::class);

        $keys = $get_whitelisted_keys->getSortedKeysWithMetadata();
        self::assertEquals('bar', $keys['foo']->category);
    }

    public function testConfigKeyHoldsSecret(): void
    {
        $class = new class {
            #[ConfigKey('summary')]
            #[ConfigKeySecret]
            public const SOME_STUFF = 'foo';
        };

        $get_whitelisted_keys = new GetWhitelistedKeys();
        $get_whitelisted_keys->addConfigClass($class::class);

        $key_metadata = $get_whitelisted_keys->getKeyMetadata($class::SOME_STUFF);
        self::assertTrue($key_metadata->is_secret);
    }
}
