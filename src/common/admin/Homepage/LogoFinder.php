<?php
/**
  * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

    public static function restoreOwnershipAndPermissions(\Psr\Log\LoggerInterface $logger): void
    {
        if (! self::isCustomLogoUsed()) {
            return;
        }

        $path = self::getCustomPath();
        $logger->debug(sprintf("Restore ownership and permissions on %s", $path));
        if (! chown($path, \ForgeConfig::getApplicationUserLogin())) {
            $logger->warning(sprintf("Impossible to chown %s to %s", $path, \ForgeConfig::getApplicationUserLogin()));
        }
        if (! chgrp($path, \ForgeConfig::getApplicationUserLogin())) {
            $logger->warning(sprintf("Impossible to chgrp %s to %s", $path, \ForgeConfig::getApplicationUserLogin()));
        }
        if (! chmod($path, 0644)) {
            $logger->warning(sprintf("Impossible to chmod %s", $path));
        }
    }
}
