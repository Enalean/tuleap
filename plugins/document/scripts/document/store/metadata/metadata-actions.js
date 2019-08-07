/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { getProjectMetadata } from "../../api/rest-querier.js";
import { handleErrors } from "../actions-helpers/handle-errors.js";

export const loadProjectMetadata = async (context, [global_context]) => {
    try {
        const project_metadata = await getProjectMetadata(global_context.state.project_id);

        context.commit("saveProjectMetadata", project_metadata);
    } catch (exception) {
        await handleErrors(global_context, exception);
    }
};
