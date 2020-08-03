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

import { AddTestDefinitionsToBacklogItemPayload, BacklogItemState } from "./type";
import { ActionContext } from "vuex";
import { RootState } from "../type";
import { FetchWrapperError, recursiveGet } from "tlp";
import {
    BacklogItem,
    BacklogItemFromREST,
    TestDefinition,
    TestDefinitionFromREST,
} from "../../type";

export async function loadBacklogItems(
    context: ActionContext<BacklogItemState, RootState>
): Promise<void> {
    context.commit("beginLoadingBacklogItems");
    try {
        await recursiveGet(
            `/api/v1/milestones/${encodeURIComponent(context.rootState.milestone_id)}/testplan`,
            {
                params: {
                    limit: 30,
                },
                getCollectionCallback: (collection: BacklogItemFromREST[]): BacklogItem[] => {
                    const backlog_items: BacklogItem[] = collection.map(
                        (item: BacklogItemFromREST): BacklogItem => {
                            const is_expanded =
                                item.id === context.rootState.expand_backlog_item_id;
                            return {
                                ...item,
                                is_expanded: is_expanded,
                                is_just_refreshed:
                                    is_expanded &&
                                    context.rootState.highlight_test_definition_id === null,
                                is_loading_test_definitions: false,
                                are_test_definitions_loaded: false,
                                has_test_definitions_loading_error: false,
                                test_definitions: [],
                            };
                        }
                    );
                    context.commit("addBacklogItems", backlog_items);

                    return backlog_items;
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

export async function loadTestDefinitions(
    context: ActionContext<BacklogItemState, RootState>,
    backlog_item: BacklogItem
): Promise<void> {
    context.commit("beginLoadingTestDefinition", backlog_item);
    try {
        await recursiveGet(
            `/api/v1/backlog_items/${encodeURIComponent(backlog_item.id)}/test_definitions`,
            {
                params: {
                    milestone_id: context.rootState.milestone_id,
                    limit: 30,
                },
                getCollectionCallback: (collection: TestDefinitionFromREST[]): TestDefinition[] => {
                    const test_definitions: TestDefinition[] = collection.map(
                        (test: TestDefinitionFromREST): TestDefinition => ({
                            ...test,
                            is_just_refreshed:
                                test.id === context.rootState.highlight_test_definition_id,
                        })
                    );
                    const payload: AddTestDefinitionsToBacklogItemPayload = {
                        backlog_item,
                        test_definitions,
                    };
                    context.commit("addTestDefinitions", payload);

                    return test_definitions;
                },
            }
        );
        context.commit("markTestDefinitionsAsBeingLoaded", backlog_item);
    } catch (e) {
        if (!isPermissionDenied(e)) {
            context.commit("loadingErrorHasBeenCatchedForTestDefinition", backlog_item);
            throw e;
        }
    } finally {
        context.commit("endLoadingTestDefinition", backlog_item);
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
