/**
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

import type { ExternalPlugins, RepositoryOwner } from "./type";

let current_user_id: number;
let current_project_id: number;
let is_administrator = false;
let locale: string;
let repositories_owners: Array<RepositoryOwner> = [];
let external_plugins: Array<ExternalPlugins> = [];
let is_old_dashboard_view_enabled = false;

export function build(
    user_id: number,
    project_id: number,
    is_user_administrator: boolean,
    user_locale: string,
    owners: Array<RepositoryOwner>,
    external_plugins_enabled: Array<ExternalPlugins>,
    is_old_pull_request_dashboard_view_enabled: boolean,
): void {
    current_user_id = user_id;
    current_project_id = project_id;
    is_administrator = Boolean(is_user_administrator);
    locale = user_locale;
    repositories_owners = owners;
    external_plugins = external_plugins_enabled;
    is_old_dashboard_view_enabled = is_old_pull_request_dashboard_view_enabled;
}

export function getUserId(): number {
    return current_user_id;
}

export function getProjectId(): number {
    return current_project_id;
}

export function getUserIsAdmin(): boolean {
    return is_administrator;
}

export function getDashCasedLocale(): string {
    return locale.replace(/_/g, "-");
}

export function getRepositoriesOwners(): Array<RepositoryOwner> {
    return repositories_owners;
}

export function getExternalPlugins(): Array<ExternalPlugins> {
    return external_plugins;
}

export function isOldPullRequestDashboardViewEnabled(): boolean {
    return is_old_dashboard_view_enabled;
}
