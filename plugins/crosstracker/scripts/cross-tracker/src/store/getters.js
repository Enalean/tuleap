/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

export const has_error_message = (state) => state.error_message !== null;

export const has_success_message = (state) => state.success_message !== null;

export const has_invalid_trackers = (state) => state.invalid_trackers.length > 0;

export const should_display_export_button = (state) => {
    if (state.error_message !== null) {
        return false;
    }
    if (state.is_user_admin === false) {
        return true;
    }

    return state.is_user_admin === true && state.invalid_trackers.length === 0;
};
