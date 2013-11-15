<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\REST;

class Header {
    const GET     = 'GET';
    const OPTIONS = 'OPTIONS';
    const PUT     = 'PUT';
    const POST    = 'POST';
    const DELETE  = 'DELETE';

    const CORS_ALLOW_METHODS = 'Access-Control-Allow-Methods';
    const ALLOW              = 'Allow';
    const LAST_MODIFIED      = 'Last-Modified';

    public static function lastModified($timestamp) {
        self::sendHeader(self::LAST_MODIFIED, date('c', $timestamp));
    }

    public static function allowOptions() {
        self::sendAllowHeaders(array(self::OPTIONS));
    }

    public static function allowOptionsGet() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET));
    }

    public static function allowOptionsPostDelete() {
        self::sendAllowHeaders(array(self::OPTIONS, self::POST, self::DELETE));
    }

    public static function allowOptionsDelete() {
        self::sendAllowHeaders(array(self::OPTIONS, self::DELETE));
    }

    private static function sendAllowHeaders($methods) {
        $methods = implode(', ', $methods);
        self::sendHeader(self::ALLOW, $methods);
        self::sendHeader(self::CORS_ALLOW_METHODS, $methods);
    }

    private static function sendHeader($name, $value) {
        header($name .': '. $value);
    }
}
