<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
    /** @var string */
    private $name;

    /** @var string */
    private $hexa_code;

    public function __construct($name, $hexa_code)
    {
        $this->name      = $name;
        $this->hexa_code = $hexa_code;
    }

    public static function buildFromDefaultVariant()
    {
        $theme_variant = new ThemeVariant();

        return self::buildFromVariant($theme_variant->getDefault());
    }

    public static function buildFromVariant(string $variant): ThemeVariantColor
    {
        switch ($variant) {
            case 'FlamingParrot_Orange':
                $color = new ThemeVariantColor('orange', '#f79514');
                break;
            case 'FlamingParrot_Green':
                $color = new ThemeVariantColor('green', '#67af45');
                break;
            case 'FlamingParrot_BlueGrey':
                $color = new ThemeVariantColor('grey', '#5b6c79');
                break;
            case 'FlamingParrot_Purple':
                $color = new ThemeVariantColor('purple', '#79558a');
                break;
            case 'FlamingParrot_Red':
                $color = new ThemeVariantColor('red', '#bd2626');
                break;
            case 'FlamingParrot_Blue':
            default:
                $color = new ThemeVariantColor('blue', '#1593c4');
        }

        return $color;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getHexaCode()
    {
        return $this->hexa_code;
    }
}
