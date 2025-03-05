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
final class ConfigDefaultThemeVariantValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testRejectUnknownVariant(): void
    {
        $this->expectException(InvalidConfigKeyValueException::class);

        ConfigDefaultThemeVariantValidator::buildSelf()
            ->checkIsValid('invalid');
    }

    public function testRejectDefaultVariantNotPartOfAllowed(): void
    {
        \ForgeConfig::set(\ThemeVariant::CONFIG_ALLOWED, 'orange,blue');

        $this->expectException(InvalidConfigKeyValueException::class);

        ConfigDefaultThemeVariantValidator::buildSelf()
            ->checkIsValid('purple');
    }

    public function testHappyPath(): void
    {
        \ForgeConfig::set(\ThemeVariant::CONFIG_ALLOWED, 'orange,blue');

        $this->expectNotToPerformAssertions();

        ConfigDefaultThemeVariantValidator::buildSelf()
            ->checkIsValid('blue');
    }
}
