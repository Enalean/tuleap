<?php
/*
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
class Event {
    
    /**
     * Periodical system check event.
     * 
     * No Parameters.
     * No expected results
     */
    const SYSTEM_CHECK = 'system_check';

    /**
     * The user has just changed his ssh authorized keys.
     * 
     * Parameters:
     *  'user' => User
     * 
     * No expected results
     */

    const EDIT_SSH_KEYS = 'edit_ssh_keys';

    /**
     * The user has just changed his email address.
     * 
     * Parameters:
     *  'user_id' => User ID
     * 
     * No expected results
     */
    const USER_EMAIL_CHANGED = 'user_email_changed';
}
?>
