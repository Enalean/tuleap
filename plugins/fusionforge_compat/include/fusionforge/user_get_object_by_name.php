<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * user_get_object_by_name() - Get User object by username.
 * user_get_object is useful so you can pool user objects/save database queries
 * You should always use this instead of instantiating the object directly
 *
 * @param	string	The unix username - required
 * @param	int	The result set handle ("SELECT * FROM USERS WHERE user_id=xx")
 * @return	a user object or false on failure
 */
function user_get_object_by_name($user_name, $res = false) {
    return UserManager::instance()->getUserByIdentifier($user_name);
}

