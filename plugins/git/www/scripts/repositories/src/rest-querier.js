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

import { recursiveGet } from "tlp";

export { getRepositoryList };

async function getRepositoryList(project_id) {
    let repository_list = await recursiveGet("/api/projects/" + project_id + "/git", {
        params: {
            query: JSON.stringify({
                scope: "project"
            }),
            limit: 50,
            offset: 0
        },
        getRepositoryList
    });

    return repository_list[0].repositories;
}
