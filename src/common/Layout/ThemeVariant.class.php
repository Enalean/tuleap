<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Layout\ThemeVariantColor;

class ThemeVariant //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const PREFERENCE_NAME = 'theme_variant';

    private ThemeVariantColor $default;

    /** @var list<ThemeVariantColor> */
    private array $allowed;

    public function __construct()
    {
        $this->default = self::normalizeVariant(ForgeConfig::get('sys_default_theme_variant'));
        $this->setAllowedVariants();
        $this->adjustDefaultVariantAccordingToAllowedVariants();
    }

    private function setAllowedVariants(): void
    {
        $comma_separated_configured_variant_names = (string) ForgeConfig::get('sys_available_theme_variants');
        $configured_variant_names                 = explode(',', $comma_separated_configured_variant_names);
        $configured_variant_names                 = array_map('trim', $configured_variant_names);
        $configured_variant_names                 = array_filter($configured_variant_names);

        $allowed = [];
        foreach ($configured_variant_names as $name) {
            $color = self::normalizeVariant($name);
            if (! isset($allowed[$color->value])) {
                $allowed[$color->value] = $color;
            }
        }

        $this->allowed = array_values($allowed);
    }

    private static function normalizeVariant(string $variant): ThemeVariantColor
    {
        $normalized = str_replace(
            'bluegrey',
            ThemeVariantColor::Grey->value,
            strtolower(str_replace('FlamingParrot_', '', $variant))
        );

        return ThemeVariantColor::buildFromName($normalized);
    }

    public static function convertToFlamingParrotVariant(ThemeVariantColor $color): string
    {
        return 'FlamingParrot_' . str_replace('Grey', 'BlueGrey', ucfirst($color->getName()));
    }

    private function adjustDefaultVariantAccordingToAllowedVariants(): void
    {
        if (! $this->isAllowed($this->default)) {
            $this->default = ThemeVariantColor::DEFAULT;
        }
    }

    public function getVariantColorForUser(PFUser $user): ThemeVariantColor
    {
        $variant = $user->getPreference(self::PREFERENCE_NAME);
        if (! $variant) {
            return $this->default;
        }

        $variant = self::normalizeVariant($variant);
        return $this->isAllowed($variant) ? $variant : $this->default;
    }

    /** @return list<ThemeVariantColor> */
    public function getAllowedVariantColors(): array
    {
        return $this->allowed;
    }

    private function isAllowed(ThemeVariantColor $variant): bool
    {
        return in_array($variant, $this->allowed, true);
    }

    public function getDefault(): ThemeVariantColor
    {
        return $this->default;
    }
}
