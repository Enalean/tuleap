<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Layout;

use ThemeVariant;
use Tuleap\ForgeConfigSandbox;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ThemeVariantTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    #[\PHPUnit\Framework\Attributes\DataProvider('provideVariant')]
    public function testDefaultVariant(string $config, ThemeVariantColor $expected): void
    {
        \ForgeConfig::set('sys_default_theme_variant', $config);
        \ForgeConfig::set('sys_available_theme_variants', 'orange,blue,grey,green,purple,red');

        $variant = new ThemeVariant();
        self::assertEquals($expected, $variant->getDefault());
    }

    public static function provideVariant(): array
    {
        return [
            'Legacy FlamingParrot_Orange' => ['FlamingParrot_Orange', ThemeVariantColor::Orange],
            'Legacy FlamingParrot_Blue' => ['FlamingParrot_Blue', ThemeVariantColor::Blue],
            'Legacy FlamingParrot_BlueGrey' => ['FlamingParrot_BlueGrey', ThemeVariantColor::Grey],
            'Legacy FlamingParrot_Green' => ['FlamingParrot_Green', ThemeVariantColor::Green],
            'Legacy FlamingParrot_Purple' => ['FlamingParrot_Purple', ThemeVariantColor::Purple],
            'Legacy FlamingParrot_Red' => ['FlamingParrot_Red', ThemeVariantColor::Red],
            'Orange' => ['Orange', ThemeVariantColor::Orange],
            'Blue' => ['Blue', ThemeVariantColor::Blue],
            'Grey' => ['Grey', ThemeVariantColor::Grey],
            'Green' => ['Green', ThemeVariantColor::Green],
            'Purple' => ['Purple', ThemeVariantColor::Purple],
            'Red' => ['Red', ThemeVariantColor::Red],
            'orange' => ['orange', ThemeVariantColor::Orange],
            'blue' => ['blue', ThemeVariantColor::Blue],
            'grey' => ['grey', ThemeVariantColor::Grey],
            'green' => ['green', ThemeVariantColor::Green],
            'purple' => ['purple', ThemeVariantColor::Purple],
            'red' => ['red', ThemeVariantColor::Red],
            'Unknown color' => ['invalid', ThemeVariantColor::Orange],
        ];
    }

    public function testNotAllowedDefaultFallbacksToOrange(): void
    {
        \ForgeConfig::set('sys_default_theme_variant', 'purple');
        \ForgeConfig::set('sys_available_theme_variants', 'orange,blue');

        $variant = new ThemeVariant();
        self::assertEquals(ThemeVariantColor::Orange, $variant->getDefault());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideConfiguredAllowedVariants')]
    public function testAllowedVariants(string $allowed, string $default, array $expected): void
    {
        \ForgeConfig::set('sys_default_theme_variant', $default);
        \ForgeConfig::set('sys_available_theme_variants', $allowed);

        $variant = new ThemeVariant();
        self::assertEqualsCanonicalizing($expected, $variant->getAllowedVariantColors());
    }

    public static function provideConfiguredAllowedVariants(): array
    {
        return [
            'Legacy FlamingParrot_Orange' => ['FlamingParrot_Orange', 'FlamingParrot_Orange', [ThemeVariantColor::Orange]],
            'Legacy FlamingParrot_Blue' => ['FlamingParrot_Blue', 'FlamingParrot_Orange', [ThemeVariantColor::Blue]],
            'Legacy FlamingParrot_BlueGrey' => ['FlamingParrot_BlueGrey', 'FlamingParrot_Orange', [ThemeVariantColor::Grey]],
            'Legacy FlamingParrot_Green' => ['FlamingParrot_Green', 'FlamingParrot_Orange', [ThemeVariantColor::Green]],
            'Legacy FlamingParrot_Purple' => ['FlamingParrot_Purple', 'FlamingParrot_Orange', [ThemeVariantColor::Purple]],
            'Legacy FlamingParrot_Red' => ['FlamingParrot_Red', 'FlamingParrot_Orange', [ThemeVariantColor::Red]],
            'Orange' => ['Orange', 'Orange', [ThemeVariantColor::Orange]],
            'Blue' => ['Blue', 'Orange', [ThemeVariantColor::Blue]],
            'Grey' => ['Grey', 'Orange', [ThemeVariantColor::Grey]],
            'Green' => ['Green', 'Orange', [ThemeVariantColor::Green]],
            'Purple' => ['Purple', 'Orange', [ThemeVariantColor::Purple]],
            'Red' => ['Red', 'Orange', [ThemeVariantColor::Red]],
            'orange' => ['orange', 'orange', [ThemeVariantColor::Orange]],
            'blue' => ['blue', 'orange', [ThemeVariantColor::Blue]],
            'grey' => ['grey', 'orange', [ThemeVariantColor::Grey]],
            'green' => ['green', 'orange', [ThemeVariantColor::Green]],
            'purple' => ['purple', 'orange', [ThemeVariantColor::Purple]],
            'red' => ['red', 'orange', [ThemeVariantColor::Red]],
            'Comma separated' => ['blue,green', 'orange', [ThemeVariantColor::Blue, ThemeVariantColor::Green]],
            'Unknown color in list' => ['blue,invalid', 'orange', [ThemeVariantColor::Blue, ThemeVariantColor::Orange]],
            'Duplicated color in list' => ['blue,blue', 'orange', [ThemeVariantColor::Blue]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideVariantsForUser')]
    public function testForUser(string $preference, string $default, string $allowed, ThemeVariantColor $expected): void
    {
        \ForgeConfig::set('sys_default_theme_variant', $default);
        \ForgeConfig::set('sys_available_theme_variants', $allowed);

        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')->with('theme_variant')->willReturn($preference);

        $variant = new ThemeVariant();
        self::assertEquals($expected, $variant->getVariantColorForUser($user));
    }

    public static function provideVariantsForUser(): array
    {
        return [
            'Legacy FlamingParrot_Orange' => ['FlamingParrot_Orange', 'FlamingParrot_Orange', 'FlamingParrot_Orange', ThemeVariantColor::Orange],
            'Legacy FlamingParrot_Blue' => ['FlamingParrot_Blue', 'FlamingParrot_Orange', 'FlamingParrot_Blue', ThemeVariantColor::Blue],
            'Legacy FlamingParrot_BlueGrey' => ['FlamingParrot_BlueGrey', 'FlamingParrot_Orange', 'FlamingParrot_BlueGrey', ThemeVariantColor::Grey],
            'Legacy FlamingParrot_Green' => ['FlamingParrot_Green', 'FlamingParrot_Orange', 'FlamingParrot_Green', ThemeVariantColor::Green],
            'Legacy FlamingParrot_Purple' => ['FlamingParrot_Purple', 'FlamingParrot_Orange', 'FlamingParrot_Purple', ThemeVariantColor::Purple],
            'Legacy FlamingParrot_Red' => ['FlamingParrot_Red', 'FlamingParrot_Orange', 'FlamingParrot_Red', ThemeVariantColor::Red],
            'Orange' => ['Orange', 'Orange', 'Orange', ThemeVariantColor::Orange],
            'Blue' => ['Blue', 'Orange', 'Blue', ThemeVariantColor::Blue],
            'Grey' => ['Grey', 'Orange', 'Grey', ThemeVariantColor::Grey],
            'Green' => ['Green', 'Orange', 'Green', ThemeVariantColor::Green],
            'Purple' => ['Purple', 'Orange', 'Purple', ThemeVariantColor::Purple],
            'Red' => ['Red', 'Orange', 'Red', ThemeVariantColor::Red],
            'orange' => ['orange', 'orange', 'orange', ThemeVariantColor::Orange],
            'blue' => ['blue', 'orange', 'blue', ThemeVariantColor::Blue],
            'grey' => ['grey', 'orange', 'grey', ThemeVariantColor::Grey],
            'green' => ['green', 'orange', 'green', ThemeVariantColor::Green],
            'purple' => ['purple', 'orange', 'purple', ThemeVariantColor::Purple],
            'red' => ['red', 'orange', 'red', ThemeVariantColor::Red],
            'Unknown color in preference' => ['invalid', 'orange', 'orange,blue', ThemeVariantColor::Orange],
            'Preference in list' => ['blue', 'orange', 'orange,blue', ThemeVariantColor::Blue],
            'Preference not allowed' => ['purple', 'orange', 'orange,blue', ThemeVariantColor::Orange],
        ];
    }

    public function testConvertToFlamingParrot(): void
    {
        self::assertEquals('FlamingParrot_Orange', ThemeVariant::convertToFlamingParrotVariant(ThemeVariantColor::Orange));
        self::assertEquals('FlamingParrot_Blue', ThemeVariant::convertToFlamingParrotVariant(ThemeVariantColor::Blue));
        self::assertEquals('FlamingParrot_BlueGrey', ThemeVariant::convertToFlamingParrotVariant(ThemeVariantColor::Grey));
        self::assertEquals('FlamingParrot_Green', ThemeVariant::convertToFlamingParrotVariant(ThemeVariantColor::Green));
        self::assertEquals('FlamingParrot_Purple', ThemeVariant::convertToFlamingParrotVariant(ThemeVariantColor::Purple));
        self::assertEquals('FlamingParrot_Red', ThemeVariant::convertToFlamingParrotVariant(ThemeVariantColor::Red));
    }
}
