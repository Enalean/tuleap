<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
class Config {
    protected static $conf = array();
    
    public static function load($file,$passfile=null) {
        if (is_file($file) && is_readable($file)) {
            include($file);
            if (is_file($passfile) && is_readable($passfile)) {
              include($passfile);
            }
            self::$conf = get_defined_vars();
            if (self::$conf['file'] === $file) {
                unset(self::$conf['file']);
            }
            return true;
        }
        return false;
    }
    
    public static function get($name, $default = false) {
        if (isset(self::$conf[$name])) {
            return self::$conf[$name];
        }
        return $default;
    }
}
?>
