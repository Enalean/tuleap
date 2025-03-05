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
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Instance;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProvideSiteLevelInitializationLanguageCodeTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testGetShortLangCodeFromSystemValue(): void
    {
        \ForgeConfig::set(\BaseLanguage::CONFIG_KEY, 'fr_FR');

        $short_lang = (new ProvideSiteLevelInitializationLanguageCode())->getLanguageCode();

        self::assertEquals('fr', $short_lang);
    }

    public function testFallbackToDefaultWhenSystemValueDoesNotHaveTheExpectedFormat(): void
    {
        \ForgeConfig::set(\BaseLanguage::CONFIG_KEY, 'invalid');

        $short_lang = (new ProvideSiteLevelInitializationLanguageCode())->getLanguageCode();

        self::assertEquals(\BaseLanguage::DEFAULT_LANG_SHORT, $short_lang);
    }
}
