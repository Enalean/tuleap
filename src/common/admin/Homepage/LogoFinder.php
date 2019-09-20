<?php
/**
  * Copyright (c) Enalean, 2015. All Rights Reserved.
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

class Admin_Homepage_LogoFinder
{

    public const PATH       = '/images/homepage-logo.png';
    public const THEME_PATH = '/themes/common';

    /** @return string */
    public static function getCurrentUrl()
    {
        if (self::isCustomLogoUsed()) {
            return self::PATH;
        }

        return self::THEME_PATH . self::PATH;
    }

    /** @return string */
    public static function getCustomPath()
    {
        return ForgeConfig::get('sys_data_dir') . self::PATH;
    }

    /** @return bool */
    public static function isCustomLogoUsed()
    {
        return is_file(self::getCustomPath());
    }
}
