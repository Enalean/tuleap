<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

enum ThemeVariantColor: string
{
    case Orange = 'orange';
    case Blue   = 'blue';
    case Green  = 'green';
    case Grey   = 'grey';
    case Purple = 'purple';
    case Red    = 'red';

    public const DEFAULT = self::Orange;

    public static function buildFromDefaultVariant(): self
    {
        $theme_variant = new \ThemeVariant();

        return $theme_variant->getDefault();
    }

    public static function buildFromName(string $name): self
    {
        return self::tryFrom($name) ?? self::DEFAULT;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Orange => _('Orange'),
            self::Blue => _('Blue'),
            self::Green => _('Green'),
            self::Grey => _('Grey'),
            self::Purple => _('Purple'),
            self::Red => _('Red'),
        };
    }

    public function getHexaCode(): string
    {
        return match ($this) {
            self::Orange => '#f79514',
            self::Blue => '#1593c4',
            self::Green => '#67af45',
            self::Grey => '#5b6c79',
            self::Purple => '#79558a',
            self::Red => '#bd2626',
        };
    }

    public function getName(): string
    {
        return $this->value;
    }
}
