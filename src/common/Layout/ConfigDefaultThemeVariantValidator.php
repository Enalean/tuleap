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

final class ConfigDefaultThemeVariantValidator implements ValueValidator
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
        $color = ThemeVariantColor::tryFrom($value);
        if (! $color) {
            throw new InvalidConfigKeyValueException("'$value' is not a valid variant. Allowed values: orange, blue, grey, green, purple, and red.");
        }

        $default_config_name = \ThemeVariant::CONFIG_DEFAULT;
        $variant             = new \ThemeVariant();
        $allowed             = $variant->getAllowedVariantColors();
        if (! in_array($color, $allowed, true)) {
            $allowed_comma_separated = implode(',', array_map(static fn(ThemeVariantColor $color) => $color->getName(), $allowed));
            throw new InvalidConfigKeyValueException("'$value' is not part of allowed theme variants '$allowed_comma_separated', this brings inconsistency. Please add '$value' to the list of allowed theme variants via $default_config_name or choose another default.");
        }
    }
}
