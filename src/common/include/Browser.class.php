<?php
/**
 * Copyright (c) Enalean, 2014-2019. All rights reserved
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

class Browser
{
    public function getDeprecatedMessage()
    {
        return '';
    }

    public function isIE11(): bool
    {
        return preg_match('~MSIE|Internet Explorer~i', $_SERVER['HTTP_USER_AGENT'])
            || (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/7.0;') !== false
                && strpos($_SERVER['HTTP_USER_AGENT'], 'rv:11.0') !== false);
    }
}
