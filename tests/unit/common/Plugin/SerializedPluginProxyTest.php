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

namespace Tuleap\Plugin;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SerializedPluginProxyTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('getDataForCaptureDefaultConfigValues')]
    public function testCaptureDefaultConfigValues(object $class, array $environment, array $expected_value): void
    {
        foreach ($environment as $env => $value) {
            putenv("$env=$value");
        }

        $proxy = new SerializedPluginProxy(new EventPluginCache());
        $proxy->addConfigClass($class::class);

        self::assertEquals($expected_value, $proxy->getDefaultVariables());

        foreach ($environment as $env => $value) {
            putenv("$env");
        }
    }

    public static function getDataForCaptureDefaultConfigValues(): iterable
    {
        return [
            'It ignores when variable has not default value' => [
                'class' => new class {
                    #[ConfigKey('Some key')]
                    public const SOME_KEY = 'some_key';
                },
                'environment' => [],
                'expected_value' => [],
            ],
            'It captures variable default value' => [
                'class' => new class {
                    #[ConfigKey('Some key')]
                    #[ConfigKeyString('foo_bar')]
                    public const SOME_KEY = 'some_key';
                },
                'environment' => [],
                'expected_value' => [
                    'some_key' => 'foo_bar',
                ],
            ],
            'It captures feature flags default value' => [
                'class' => new class {
                    #[FeatureFlagConfigKey('Some key')]
                    #[ConfigKeyString('foo_bar')]
                    public const SOME_KEY = 'some_key';
                },
                'environment' => [],
                'expected_value' => [
                    \ForgeConfig::FEATURE_FLAG_PREFIX . 'some_key' => 'foo_bar',
                ],
            ],
            'It captures variable from environment' => [
                'class' => new class {
                    #[ConfigKey('Some key')]
                    public const SOME_KEY = 'some_key';
                },
                'environment' => [
                    'TULEAP_SOME_KEY' => 'foo',
                ],
                'expected_value' => [
                    'some_key' => 'foo',
                ],
            ],
            'It captures variable from environment with empty value' => [
                'class' => new class {
                    #[ConfigKey('Some key')]
                    public const SOME_KEY = 'some_key';
                },
                'environment' => [
                    'TULEAP_SOME_KEY' => '',
                ],
                'expected_value' => [
                    'some_key' => '',
                ],
            ],
            'Environment overrides default value' => [
                'class' => new class {
                    #[ConfigKey('Some key')]
                    #[ConfigKeyString('foo_bar')]
                    public const SOME_KEY = 'some_key';
                },
                'environment' => [
                    'TULEAP_SOME_KEY' => 'foo',
                ],
                'expected_value' => [
                    'some_key' => 'foo',
                ],
            ],
            'Environment with empty value overrides default value' => [
                'class' => new class {
                    #[ConfigKey('Some key')]
                    #[ConfigKeyString('foo_bar')]
                    public const SOME_KEY = 'some_key';
                },
                'environment' => [
                    'TULEAP_SOME_KEY' => '',
                ],
                'expected_value' => [
                    'some_key' => '',
                ],
            ],
        ];
    }
}
