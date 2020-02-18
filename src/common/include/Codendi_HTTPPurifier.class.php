<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * Clean-up string for http header output.
 *
 * This class aims to purify the header to prevent header injections
 */
class Codendi_HTTPPurifier
{
    public static function instance()
    {
        static $__Codendi_HTTPPurifier_instance;
        if (!$__Codendi_HTTPPurifier_instance) {
            $__Codendi_HTTPPurifier_instance = new Codendi_HTTPPurifier();
        }
        return $__Codendi_HTTPPurifier_instance;
    }

    public function purify($s)
    {
        $clean = preg_replace('/(\n|\r|\0).*/', '', $s);
        return $clean;
    }
}
