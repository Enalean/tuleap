/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

const getBaseUrl = (location: Location, project_id: number, repository_id: number): URL => {
    const url = new URL("/plugins/git/", location.origin);

    url.searchParams.set("action", "pull-requests");
    url.searchParams.set("group_id", String(project_id));
    url.searchParams.set("repo_id", String(repository_id));

    return url;
};

export const getOldPullRequestsDashboardUrl = (
    location: Location,
    project_id: number,
    repository_id: number,
): URL => {
    const url = getBaseUrl(location, project_id, repository_id);
    url.hash = "#/dashboard";

    return url;
};

export const getPullRequestsHomepageUrl = (
    location: Location,
    project_id: number,
    repository_id: number,
): URL => {
    const url = getBaseUrl(location, project_id, repository_id);
    url.searchParams.set("tab", "homepage");

    return url;
};
