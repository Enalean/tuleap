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

use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\ForgeConfigSandbox;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigAvailableThemeVariantValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testRejectUnknownVariant(): void
    {
        $this->expectException(InvalidConfigKeyValueException::class);

        ConfigAvailableThemeVariantValidator::buildSelf()
            ->checkIsValid('blue,invalid');
    }

    public function testRejectMultipleOccurencesOfSameVariant(): void
    {
        $this->expectException(InvalidConfigKeyValueException::class);

        ConfigAvailableThemeVariantValidator::buildSelf()
            ->checkIsValid('blue,orange,blue');
    }

    public function testRejectWenDefaultIsNotPartOfAvailableVariant(): void
    {
        \ForgeConfig::set(\ThemeVariant::CONFIG_DEFAULT, 'purple');

        $this->expectException(InvalidConfigKeyValueException::class);

        ConfigAvailableThemeVariantValidator::buildSelf()
            ->checkIsValid('blue,orange');
    }

    public function testHappyPath(): void
    {
        \ForgeConfig::set(\ThemeVariant::CONFIG_DEFAULT, 'purple');

        $this->expectNotToPerformAssertions();

        ConfigAvailableThemeVariantValidator::buildSelf()
            ->checkIsValid('blue,purple');
    }
}
