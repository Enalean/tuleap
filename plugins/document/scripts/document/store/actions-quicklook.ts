/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

import { getItem } from "../api/rest-querier";
import { handleErrorsForDocument } from "./actions-helpers/handle-errors";
import type { RootState } from "../type";
import type { ActionContext } from "vuex";

export const toggleQuickLook = async (
    context: ActionContext<RootState, RootState>,
    item_id: number,
): Promise<void> => {
    try {
        context.commit("beginLoadingCurrentlyPreviewedItem");
        const item = await getItem(item_id);

        context.commit("updateCurrentlyPreviewedItem", item);
        context.commit("toggleQuickLook", true);
    } catch (exception) {
        await handleErrorsForDocument(context, exception);
    } finally {
        context.commit("stopLoadingCurrentlyPreviewedItem");
    }
};

export const removeQuickLook = (context: ActionContext<RootState, RootState>): void => {
    context.commit("updateCurrentlyPreviewedItem", null);
    context.commit("toggleQuickLook", false);
};
