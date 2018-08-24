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

import { getBranches } from "../api/rest-querier.js";

export async function init(context, { repository_id, parent_repository_id }) {
    const branches = await getBranches(repository_id);
    context.commit("setSourceBranches", branches);

    if (parent_repository_id) {
        const parent_repository_branches = await getBranches(parent_repository_id);
        context.commit("setDestinationBranches", branches.concat(parent_repository_branches));
    } else {
        context.commit("setDestinationBranches", branches);
    }
}
