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

final class ProvideSiteLevelInitializationLanguageCode implements InitializationLanguageCodeProvider
{
    private readonly string $sys_lang_value;

    public function __construct()
    {
        $this->sys_lang_value = \ForgeConfig::get(\BaseLanguage::CONFIG_KEY, \BaseLanguage::DEFAULT_LANG);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getLanguageCode(): string
    {
        return \Psl\Str\before($this->sys_lang_value, '_') ?? \BaseLanguage::DEFAULT_LANG_SHORT;
    }
}
