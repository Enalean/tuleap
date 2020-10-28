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
export { credentialsAreEmpty, serverUrlIsValid, formatUrl };

function credentialsAreEmpty(credentials) {
    return (
        credentials.token === undefined ||
        credentials.token === "" ||
        credentials.server_url === undefined ||
        credentials.server_url === ""
    );
}

function serverUrlIsValid(server_url) {
    const reg_exp = new RegExp("^(http://|https://|\\?)");

    return reg_exp.test(server_url);
}

function formatUrl(server_url) {
    let url = server_url;
    if (server_url.slice(-1) === "/") {
        url = server_url.slice(0, -1);
    }

    return url + "/api/v4/projects?membership=true&per_page=20&min_access_level=40";
}
