<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

use Tuleap\BurningParrotCompatiblePageDetector;

/**
 * Instanciate the right theme according to user and platform preferences
 * and theme availability
 */
class ThemeManager
{
    private static $BURNING_PARROT   = 'BurningParrot';
    private static $FLAMING_PARROT   = 'FlamingParrot';
    private static $LEGACY_EXTENSION = '_Theme.class.php';
    private static $PSR2_EXTENSION   = 'Theme.php';

    /**
     * @var BurningParrotCompatiblePageDetector
     */
    private $page_detector;

    public function __construct(
        BurningParrotCompatiblePageDetector $page_detector
    ) {
        $this->page_detector = $page_detector;
    }

    public function getTheme(PFUser $current_user)
    {
        if ($this->page_detector->isInCompatiblePage($current_user)) {
            $theme = $this->getStandardTheme($current_user, self::$BURNING_PARROT);
        } else {
            $theme = $this->getStandardTheme($current_user, self::$FLAMING_PARROT);
        }

        if ($theme === null && ! IS_SCRIPT) {
            throw new Exception('No theme has been found. Do you have installed BurningParrot?');
        }

        $GLOBALS['Response'] = $theme;
        return $GLOBALS['Response'];
    }

    /**
     * @return \Tuleap\Theme\BurningParrot\BurningParrotTheme
     */
    public function getBurningParrot(PFUser $current_user)
    {
        return $this->getStandardTheme($current_user, self::$BURNING_PARROT);
    }

    private function getStandardTheme(PFUser $current_user, $name)
    {
        $theme_basedir_root = __DIR__ . '/../../www/themes/';
        if ($this->themeExists($theme_basedir_root, $name)) {
            $GLOBALS['sys_user_theme'] = $name;
            $path = $this->getThemeClassPath($theme_basedir_root, $name);

            return $this->instantiateTheme($current_user, $name, $path, '/themes/' . $name);
        }
        return null;
    }

    private function instantiateTheme(PFUser $current_user, $name, $path, $webroot)
    {
        if (preg_match('`' . preg_quote(self::$LEGACY_EXTENSION, '`') . '$`', $path)) {
            $klass = $name . '_Theme';
            include_once $path;
            return new $klass($webroot);
        }

        $klass = "Tuleap\\Theme\\{$name}\\{$name}Theme";
        include_once dirname($path) . "/{$name}Theme.php";
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
}
