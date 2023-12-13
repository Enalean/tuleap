/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

export const buildBaseUrl = (
    location: Location,
    repository_id: number,
    project_id: number,
): URL => {
    const base_url = new URL("/plugins/git/", location.origin);
    base_url.searchParams.set("action", "pull-requests");
    base_url.searchParams.set("repo_id", encodeURIComponent(repository_id));
    base_url.searchParams.set("group_id", encodeURIComponent(project_id));

    return base_url;
};

export const buildPullRequestOverviewUrl = (base_url: URL, pull_request_id: number): URL => {
    const url = new URL(base_url);

    url.searchParams.set("tab", "overview");
    url.hash = `#/pull-requests/${encodeURIComponent(pull_request_id)}/overview`;

    return url;
};
