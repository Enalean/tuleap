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

class ThemeVariantColor // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private const ORANGE = 'orange';
    private const GREEN  = 'green';
    private const GREY   = 'grey';
    private const PURPLE = 'purple';
    private const RED    = 'red';
    private const BLUE   = 'blue';

    private const VARIANT_ORANGE    = 'FlamingParrot_Orange';
    private const VARIANT_GREEN     = 'FlamingParrot_Green';
    private const VARIANT_BLUE_GREY = 'FlamingParrot_BlueGrey';
    private const VARIANT_PURPLE    = 'FlamingParrot_Purple';
    private const VARIANT_RED       = 'FlamingParrot_Red';
    private const VARIANT_BLUE      = 'FlamingParrot_Blue';

    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $hexa_code;
    /**
     * @var string
     */
    private $label;
    /**
     * @var string
     */
    private $variant;

    private function __construct(string $name, string $label, string $hexa_code, string $variant)
    {
        $this->name      = $name;
        $this->hexa_code = $hexa_code;
        $this->label     = $label;
        $this->variant   = $variant;
    }

    public static function buildFromDefaultVariant()
    {
        $theme_variant = new ThemeVariant();

        return self::buildFromVariant($theme_variant->getDefault());
    }

    public static function buildFromVariant(string $variant): ThemeVariantColor
    {
        switch ($variant) {
            case self::VARIANT_ORANGE:
                $color = new ThemeVariantColor(self::ORANGE, _('Orange'), '#f79514', $variant);
                break;
            case self::VARIANT_GREEN:
                $color = new ThemeVariantColor(self::GREEN, _('Green'), '#67af45', $variant);
                break;
            case self::VARIANT_BLUE_GREY:
                $color = new ThemeVariantColor(self::GREY, _('Grey'), '#5b6c79', $variant);
                break;
            case self::VARIANT_PURPLE:
                $color = new ThemeVariantColor(self::PURPLE, _('Purple'), '#79558a', $variant);
                break;
            case self::VARIANT_RED:
                $color = new ThemeVariantColor(self::RED, _('Red'), '#bd2626', $variant);
                break;
            case self::VARIANT_BLUE:
            default:
                $color = new ThemeVariantColor(self::BLUE, _('Blue'), '#1593c4', $variant);
        }

        return $color;
    }

    public static function buildFromName(string $name): ThemeVariantColor
    {
        switch ($name) {
            case self::ORANGE:
                $color = new ThemeVariantColor($name, _('Orange'), '#f79514', self::VARIANT_ORANGE);
                break;
            case self::GREEN:
                $color = new ThemeVariantColor($name, _('Green'), '#67af45', self::VARIANT_GREEN);
                break;
            case self::GREY:
                $color = new ThemeVariantColor($name, _('Grey'), '#5b6c79', self::VARIANT_BLUE_GREY);
                break;
            case self::PURPLE:
                $color = new ThemeVariantColor($name, _('Purple'), '#79558a', self::VARIANT_PURPLE);
                break;
            case self::RED:
                $color = new ThemeVariantColor($name, _('Red'), '#bd2626', self::VARIANT_RED);
                break;
            case self::BLUE:
            default:
                $color = new ThemeVariantColor($name, _('Blue'), '#1593c4', self::VARIANT_BLUE);
        }

        return $color;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHexaCode(): string
    {
        return $this->hexa_code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }
}
