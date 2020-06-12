/*
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { BacklogItemState } from "./type";
import { ActionContext } from "vuex";
import { RootState } from "../type";
import { FetchWrapperError, recursiveGet } from "tlp";
import { BacklogItem } from "../../type";

export async function loadBacklogItems(
    context: ActionContext<BacklogItemState, RootState>
): Promise<void> {
    context.commit("beginLoadingBacklogItems");
    try {
        await recursiveGet(
            `/api/v1/milestones/${encodeURIComponent(context.rootState.milestone_id)}/content`,
            {
                params: {
                    limit: 100,
                },
                getCollectionCallback: (collection: BacklogItem[]): BacklogItem[] => {
                    context.commit("addBacklogItems", collection);

                    return collection;
                },
            }
        );
    } catch (e) {
        if (!isPermissionDenied(e)) {
            context.commit("loadingErrorHasBeenCatched");
            throw e;
        }
    } finally {
        context.commit("endLoadingBacklogItems");
    }
}

function isPermissionDenied(error: Error | FetchWrapperError): boolean {
    if (!isAFetchWrapperError(error)) {
        return false;
    }

    return error.response.status === 403;
}

function isAFetchWrapperError(error: Error | FetchWrapperError): error is FetchWrapperError {
    return "response" in error;
}
