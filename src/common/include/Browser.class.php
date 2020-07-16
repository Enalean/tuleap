<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return preg_match('~MSIE|Internet Explorer~i', $user_agent)
            || (strpos($user_agent, 'Trident/7.0;') !== false
                && strpos($user_agent, 'rv:11.0') !== false);
    }

    public function isEdgeLegacy(): bool
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return strpos($user_agent, "Edge") !== false;
    }
}
