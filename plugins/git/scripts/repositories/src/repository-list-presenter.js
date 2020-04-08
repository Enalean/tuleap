/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

export {
    build,
    getUserId,
    getProjectId,
    getUserIsAdmin,
    getDashCasedLocale,
    getRepositoriesOwners,
    getExternalPlugins,
};

let current_user_id;
let current_project_id;
let is_administrator = false;
let locale;
let repositories_owners = [];
let external_plugins = [];

function build(
    user_id,
    project_id,
    is_user_administrator,
    user_locale,
    owners,
    external_plugins_enabled
) {
    current_user_id = user_id;
    current_project_id = project_id;
    is_administrator = Boolean(is_user_administrator);
    locale = user_locale;
    repositories_owners = owners;
    external_plugins = external_plugins_enabled;
}

function getUserId() {
    return current_user_id;
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

function getRepositoriesOwners() {
    return repositories_owners;
}

function getExternalPlugins() {
    return external_plugins;
}
