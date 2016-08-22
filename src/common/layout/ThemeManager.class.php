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
class ThemeManager {

    public function getTheme(PFUser $current_user) {
        $GLOBALS['Response'] = $this->getFirstValidTheme(array(
            $current_user->getTheme(),
            $GLOBALS['sys_themedefault'],
            'FlamingParrot',
            'Tuleap',
        ));
        return $GLOBALS['Response'];
    }

    private function getFirstValidTheme(array $theme_names) {
        foreach ($theme_names as $name) {
            $theme = $this->getValidTheme($name);
            if ($theme !== false) {
                return $theme;
            }
        }
        return new DivBasedTabbedLayout('/themes/common');
    }

    private function getValidTheme($name) {
        $theme = $this->getStandardTheme($name);
        if ($theme === false) {
            $theme = $this->getCustomTheme($name);
        }
        return $theme;
    }

    public function getStandardTheme($name) {
        if ($this->themeExists($GLOBALS['sys_themeroot'], $name)) {
            $GLOBALS['sys_is_theme_custom'] = false;
            $GLOBALS['sys_user_theme'] = $name;
            include_once $this->getThemeClassPath($GLOBALS['sys_themeroot'], $name);
            $klass = $this->getThemeClassName($name);
            return new $klass('/themes/'.$name);
        }
        return false;
    }

    private function getCustomTheme($name) {
        if ($this->themeExists($GLOBALS['sys_custom_themeroot'], $name)) {
            $GLOBALS['sys_is_theme_custom'] = true;
            $GLOBALS['sys_user_theme'] = $name;
            include_once $this->getThemeClassPath($GLOBALS['sys_custom_themeroot'], $name);
            $klass = $this->getThemeClassName($name);
            return new $klass('/custom/'.$name);
        }
        return false;
    }

    private function themeExists($base_dir, $name) {
        return file_exists($this->getThemeClassPath($base_dir, $name));
    }

    private function getThemeClassPath($base_dir, $name) {
        return $base_dir . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $this->getThemeClassName($name) . '.class.php';
    }

    private function getThemeClassName($name) {
        return $name . '_Theme';
    }

    public function isThemeValid($name)
    {
        return $this->themeExists(ForgeConfig::get('sys_themeroot'), $name) ||
               $this->themeExists(ForgeConfig::get('sys_custom_themeroot'), $name);
    }

}
