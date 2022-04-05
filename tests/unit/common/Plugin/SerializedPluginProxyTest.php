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

final class SerializedPluginProxyTest extends TestCase
{
    /**
     * @dataProvider getDataForCaptureDefaultConfigValues
     */
    public function testCaptureDefaultConfigValues(object $class, array $expected_value): void
    {
        $proxy = new SerializedPluginProxy(new EventPluginCache());

        $proxy->addConfigClass($class::class);

        self::assertEquals($expected_value, $proxy->getDefaultVariables());
    }

    public function getDataForCaptureDefaultConfigValues(): iterable
    {
        return [
            'It ignores when variable has not default value' => [
                'class' => new class {
                    #[ConfigKey('Some key')]
                    public const SOME_KEY = 'some_key';
                },
                'expected_value' => [],
            ],
            'It captures variable default value' => [
                'class' => new class {
                    #[ConfigKey('Some key')]
                    #[ConfigKeyString('foo_bar')]
                    public const SOME_KEY = 'some_key';
                },
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
                'expected_value' => [
                    \ForgeConfig::FEATURE_FLAG_PREFIX . 'some_key' => 'foo_bar',
                ],
            ],
        ];
    }
}
