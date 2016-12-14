<?php
/**
 * Copyright (c) Enalean, 2014-2016. All Rights Reserved.
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

/**
 * Instanciate the right theme according to user and platform preferences
 * and theme availability
 */
class ThemeManager
{
    private static $BURNING_PARROT   = 'BurningParrot';
    private static $LEGACY_EXTENSION = '_Theme.class.php';
    private static $PSR2_EXTENSION   = 'Theme.php';

    public function getTheme(PFUser $current_user)
    {
        $GLOBALS['Response'] = $this->getFirstValidTheme($current_user, array(
            $current_user->getTheme(),
            $GLOBALS['sys_themedefault'],
            'FlamingParrot',
            'Tuleap',
        ));
        return $GLOBALS['Response'];
    }

    private function getFirstValidTheme(PFUser $current_user, array $theme_names)
    {
        if ($this->isInSiteAdmin($current_user)) {
            return $this->getValidTheme($current_user, self::$BURNING_PARROT);
        }

        foreach ($theme_names as $name) {
            $theme = $this->getValidTheme($current_user, $name);
            if ($theme !== false && $this->isAllowedTheme($current_user, $name)) {
                return $theme;
            }
        }

        return new DivBasedTabbedLayout('/themes/common');
    }

    private function isAllowedTheme(PFUser $current_user, $name)
    {
        if ($name === self::$BURNING_PARROT) {
            return $this->isInSiteAdmin($current_user);
        }

        return true;
    }

    private function isInSiteAdmin(PFUser $current_user)
    {
        if (IS_SCRIPT) {
            return false;
        }

        $is_in_site_admin = false;
        EventManager::instance()->processEvent(
            Event::IS_IN_SITEADMIN,
            array(
                'is_in_siteadmin' => &$is_in_site_admin
            )
        );

        $is_in_site_admin = $is_in_site_admin ||
            preg_match(
                '`(
                    ^/admin/
                    |
                    ^/admin/news.php
                    |
                    ^/tracker/admin/restore.php
                )`x',
                $_SERVER['REQUEST_URI']
            ) && ! preg_match(
                '`(
                    ^/admin/register_admin.php
                )`x',
                $_SERVER['REQUEST_URI']
            );

        return $current_user->isSuperUser() && $is_in_site_admin;
    }

    private function getValidTheme(PFUser $current_user, $name)
    {
        $theme = $this->getStandardTheme($current_user, $name);
        if ($theme === false) {
            $theme = $this->getCustomTheme($current_user, $name);
        }

        return $theme;
    }

    private function getStandardTheme(PFUser $current_user, $name)
    {
        if ($this->themeExists($GLOBALS['sys_themeroot'], $name)) {
            $GLOBALS['sys_is_theme_custom'] = false;
            $GLOBALS['sys_user_theme'] = $name;
            $path = $this->getThemeClassPath($GLOBALS['sys_themeroot'], $name);

            return $this->instantiateTheme($current_user, $name, $path, '/themes/'.$name);
        }
        return false;
    }

    private function getCustomTheme(PFUser $current_user, $name)
    {
        if ($this->themeExists($GLOBALS['sys_custom_themeroot'], $name)) {
            $GLOBALS['sys_is_theme_custom'] = true;
            $GLOBALS['sys_user_theme'] = $name;
            $path = $this->getThemeClassPath($GLOBALS['sys_custom_themeroot'], $name);

            return $this->instantiateTheme($current_user, $name, $path, '/custom/'.$name);
        }
        return false;
    }

    private function instantiateTheme(PFUser $current_user, $name, $path, $webroot)
    {
        if (preg_match('`'. preg_quote(self::$LEGACY_EXTENSION) .'$`', $path)) {
            $klass = $name . '_Theme';
            include_once $path;
            return new $klass($webroot);
        }

        $klass = "Tuleap\\Theme\\{$name}\\{$name}Theme";
        include_once dirname($path) . '/autoload.php';
        return new $klass($webroot, $current_user);
    }

    private function themeExists($base_dir, $name)
    {
        return file_exists($this->getThemeClassPath($base_dir, $name));
    }

    private function getThemeClassPath($base_dir, $name)
    {
        return $this->getThemeBasePath($base_dir, $name) . DIRECTORY_SEPARATOR . $this->getThemeFilename($base_dir, $name);
    }

    private function getThemeBasePath($base_dir, $name)
    {
        return "$base_dir/$name";
    }

    private function getThemeFilename($base_dir, $name)
    {
        $path            = $this->getThemeBasePath($base_dir, $name);
        $legacy_filename = $name . self::$LEGACY_EXTENSION;

        if (is_file("$path/$legacy_filename")) {
            return $legacy_filename;
        } else {
            return $name . self::$PSR2_EXTENSION;
        }
    }

    public function isThemeValid($name)
    {
        return $this->themeExists(ForgeConfig::get('sys_themeroot'), $name) ||
               $this->themeExists(ForgeConfig::get('sys_custom_themeroot'), $name);
    }
}
