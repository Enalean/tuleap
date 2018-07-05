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

export { build, getProjectId, getUserIsAdmin, getDashCasedLocale };

let current_project_id;
let is_administrator = false;
let locale;

function build(project_id, is_user_administrator, user_locale) {
    current_project_id = project_id;
    is_administrator = is_user_administrator;
    locale = user_locale;
}

function getProjectId() {
    return current_project_id;
}

function getUserIsAdmin() {
    return is_administrator;
}

function getDashCasedLocale() {
    return locale.replace(/_/g, "-");
}
