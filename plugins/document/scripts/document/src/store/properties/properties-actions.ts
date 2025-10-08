/*
 * Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { getProjectProperties } from "../../api/properties-rest-querier";
import type { ActionContext } from "vuex";
import type { RootState } from "../../type";

export interface PropertiesActions {
    readonly loadProjectProperties: typeof loadProjectProperties;
}

export const loadProjectProperties = async (
    context: ActionContext<RootState, RootState>,
    project_id: number,
): Promise<void> => {
    try {
        const project_properties = await getProjectProperties(project_id);

        context.commit("saveProjectProperties", project_properties);
    } catch (exception) {
        await context.dispatch("error/handleGlobalModalError", exception, { root: true });
    }
};
