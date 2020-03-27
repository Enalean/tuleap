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

import { getParents } from "../../api/rest-querier.js";
import { handleErrors } from "./handle-errors.js";

export async function loadAscendantHierarchy(context, folder_id, loading_current_folder_promise) {
    try {
        context.commit("beginLoadingAscendantHierarchy");
        context.commit("resetAscendantHierarchy");

        const [parents, current_folder] = await Promise.all([
            getParents(folder_id),
            loading_current_folder_promise,
        ]);

        parents.shift();
        parents.push(current_folder);

        context.commit("saveAscendantHierarchy", parents);
        context.commit("setCurrentFolder", current_folder);
    } catch (exception) {
        return handleErrors(context, exception);
    } finally {
        context.commit("stopLoadingAscendantHierarchy");
    }
}
