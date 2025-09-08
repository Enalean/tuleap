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
use Tuleap\Config\ValueValidator;

final class ConfigAvailableThemeVariantValidator implements ValueValidator
{
    private function __construct()
    {
    }

    #[\Override]
    public static function buildSelf(): ValueValidator
    {
        return new self();
    }

    #[\Override]
    public function checkIsValid(string $value): void
    {
        $variant_names = explode(',', $value);
        $variant_names = array_map('trim', $variant_names);
        $variant_names = array_filter($variant_names);

        $already_seen = [];

        foreach ($variant_names as $name) {
            $color = ThemeVariantColor::tryFrom($name);
            if (! $color) {
                throw new InvalidConfigKeyValueException("'$name' is not a valid variant. Allowed values: orange, blue, grey, green, purple, and red.");
            }

            if (isset($already_seen[$name])) {
                throw new InvalidConfigKeyValueException("'$name' cannot be listed more than once.");
            }

            $already_seen[$name] = true;
        }

        $default_config_name = \ThemeVariant::CONFIG_DEFAULT;
        $default             = \ForgeConfig::get($default_config_name);
        if (! isset($already_seen[$default])) {
            throw new InvalidConfigKeyValueException("Default theme '$default' is not part of '$value', this brings inconsistency. Please add '$default' to the list of allowed theme variants or change $default_config_name first.");
        }
    }
}
