/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */
import type { GitLabCredentials } from "../type";

export {
    credentialsAreEmpty,
    serverUrlIsValid,
    formatUrlToGetAllProject,
    formatUrlToGetProjectFromId,
};

function credentialsAreEmpty(credentials: GitLabCredentials): boolean {
    return (
        credentials.token === undefined ||
        credentials.token === "" ||
        credentials.server_url === undefined ||
        credentials.server_url === ""
    );
}

function serverUrlIsValid(server_url: string): boolean {
    const reg_exp = new RegExp("^(http://|https://|\\?)");

    return reg_exp.test(server_url);
}

function formatUrlToGetAllProject(server_url: string): string {
    let url = server_url;
    if (server_url.slice(-1) === "/") {
        url = server_url.slice(0, -1);
    }

    return url + "/api/v4/projects?membership=true&per_page=20&min_access_level=40";
}

function formatUrlToGetProjectFromId(server_url: string, repository_id: number): string {
    let url = server_url;
    if (server_url.slice(-1) === "/") {
        url = server_url.slice(0, -1);
    }

    return url + "/api/v4/projects/" + repository_id;
}
