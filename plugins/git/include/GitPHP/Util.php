<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Util
 *
 * Utility functions
 *
 */
/**
 * Util class
 */
class Util
{

    /**
     * AddSlash
     *
     * Adds a trailing slash to a directory path if necessary
     *
     * @access public
     * @static
     * @param string $path path to add slash to
     * @return string $path with a trailing slash
     */
    public static function AddSlash($path) // @codingStandardsIgnoreLine
    {
        if (empty($path)) {
            return $path;
        }

        $end = substr($path, -1);

        if (!(( ($end == '/') || ($end == ':')))) {
            $path .= '/';
        }

        return $path;
    }

    /**
     * MakeSlug
     *
     * Turn a string into a filename-friendly slug
     *
     * @access public
     * @param string $str string to slugify
     * @static
     * @return string slug
     */
    public static function MakeSlug($str) // @codingStandardsIgnoreLine
    {
        $from = array(
            '/'
        );
        $to = array(
            '-'
        );
        return str_replace($from, $to, $str);
    }
}
