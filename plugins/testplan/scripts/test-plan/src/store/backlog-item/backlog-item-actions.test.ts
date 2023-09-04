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

import type { BacklogItemState } from "./type";
import type { ActionContext } from "vuex";
import type { RootState } from "../type";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { loadBacklogItems, loadTestDefinitions } from "./backlog-item-actions";
import type { BacklogItem, TestDefinition } from "../../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("BacklogItem state actions", () => {
    let context: ActionContext<BacklogItemState, RootState>;
    let tlpRecursiveGetMock: jest.SpyInstance;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42,
                expand_backlog_item_id: 1000,
                highlight_test_definition_id: 1001,
            } as RootState,
        } as unknown as ActionContext<BacklogItemState, RootState>;
        tlpRecursiveGetMock = jest.spyOn(tlp_fetch, "recursiveGet");
    });

    describe("loadBacklogItems", () => {
        it("Retrieves all backlog items for milestone", async () => {
            tlpRecursiveGetMock.mockImplementation((route, config) => {
                config.getCollectionCallback([{ id: 123 }, { id: 124 }] as BacklogItem[]);
            });

            await loadBacklogItems(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingBacklogItems");
            expect(context.commit).toHaveBeenCalledWith("endLoadingBacklogItems");
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(`/api/v1/milestones/42/testplan`, {
                params: { limit: 30 },
                getCollectionCallback: expect.any(Function),
            });
            expect(context.commit).toHaveBeenCalledWith("addBacklogItems", [
                {
                    id: 123,
                    is_expanded: false,
                    is_just_refreshed: false,
                    are_test_definitions_loaded: false,
                    is_loading_test_definitions: false,
                    has_test_definitions_loading_error: false,
                    test_definitions: [] as TestDefinition[],
                },
                {
                    id: 124,
                    is_expanded: false,
                    is_just_refreshed: false,
                    are_test_definitions_loaded: false,
                    is_loading_test_definitions: false,
                    has_test_definitions_loading_error: false,
                    test_definitions: [] as TestDefinition[],
                },
            ] as BacklogItem[]);
        });

        it("Marks an item as expanded if app wants to expand it", async () => {
            tlpRecursiveGetMock.mockImplementation((route, config) => {
                config.getCollectionCallback([
                    { id: 123 },
                    { id: 124 },
                    { id: 1000 },
                ] as BacklogItem[]);
            });

            await loadBacklogItems(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingBacklogItems");
            expect(context.commit).toHaveBeenCalledWith("endLoadingBacklogItems");
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(`/api/v1/milestones/42/testplan`, {
                params: { limit: 30 },
                getCollectionCallback: expect.any(Function),
            });
            expect(context.commit).toHaveBeenCalledWith("addBacklogItems", [
                {
                    id: 123,
                    is_expanded: false,
                    is_just_refreshed: false,
                    are_test_definitions_loaded: false,
                    is_loading_test_definitions: false,
                    has_test_definitions_loading_error: false,
                    test_definitions: [] as TestDefinition[],
                },
                {
                    id: 124,
                    is_expanded: false,
                    is_just_refreshed: false,
                    are_test_definitions_loaded: false,
                    is_loading_test_definitions: false,
                    has_test_definitions_loading_error: false,
                    test_definitions: [] as TestDefinition[],
                },
                {
                    id: 1000,
                    is_expanded: true,
                    is_just_refreshed: false,
                    are_test_definitions_loaded: false,
                    is_loading_test_definitions: false,
                    has_test_definitions_loading_error: false,
                    test_definitions: [] as TestDefinition[],
                },
            ] as BacklogItem[]);
        });

        it("Marks an item as just refreshed if app wants to expand it and no test def needs to be highlighted", async () => {
            tlpRecursiveGetMock.mockImplementation((route, config) => {
                config.getCollectionCallback([{ id: 1000 }] as BacklogItem[]);
            });

            context.rootState = { ...context.rootState, highlight_test_definition_id: null };

            await loadBacklogItems(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingBacklogItems");
            expect(context.commit).toHaveBeenCalledWith("endLoadingBacklogItems");
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(`/api/v1/milestones/42/testplan`, {
                params: { limit: 30 },
                getCollectionCallback: expect.any(Function),
            });
            expect(context.commit).toHaveBeenCalledWith("addBacklogItems", [
                {
                    id: 1000,
                    is_expanded: true,
                    is_just_refreshed: true,
                    are_test_definitions_loaded: false,
                    is_loading_test_definitions: false,
                    has_test_definitions_loading_error: false,
                    test_definitions: [] as TestDefinition[],
                },
            ] as BacklogItem[]);
        });

        it("Catches error", async () => {
            const error = new Error();
            tlpRecursiveGetMock.mockRejectedValue(error);

            await expect(loadBacklogItems(context)).rejects.toThrow();

            expect(context.commit).toHaveBeenCalledWith("beginLoadingBacklogItems");
            expect(context.commit).toHaveBeenCalledWith("loadingErrorHasBeenCatched");
            expect(context.commit).toHaveBeenCalledWith("endLoadingBacklogItems");
        });

        it("Does not catch 403 so that empty state can be displayed instead of error state", async () => {
            const error = new FetchWrapperError("Forbidden", { status: 403 } as Response);
            tlpRecursiveGetMock.mockRejectedValue(error);

            await loadBacklogItems(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingBacklogItems");
            expect(context.commit).not.toHaveBeenCalledWith("loadingErrorHasBeenCatched");
            expect(context.commit).toHaveBeenCalledWith("endLoadingBacklogItems");
        });
    });

    describe("loadTestDefinitions", () => {
        it("Retrieves the test definitions of a backlog item", async () => {
            tlpRecursiveGetMock.mockImplementation((route, config) => {
                config.getCollectionCallback([{ id: 123 }, { id: 124 }] as TestDefinition[]);
            });

            const backlog_item = { id: 101 } as BacklogItem;

            await loadTestDefinitions(context, backlog_item);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingTestDefinition", backlog_item);
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
                `/api/v1/backlog_items/101/test_definitions`,
                {
                    params: { milestone_id: 42, limit: 30 },
                    getCollectionCallback: expect.any(Function),
                },
            );
            expect(context.commit).toHaveBeenCalledWith("addTestDefinitions", {
                backlog_item,
                test_definitions: [
                    { id: 123, is_just_refreshed: false },
                    { id: 124, is_just_refreshed: false },
                ] as TestDefinition[],
            });
            expect(context.commit).toHaveBeenCalledWith(
                "markTestDefinitionsAsBeingLoaded",
                backlog_item,
            );
            expect(context.commit).toHaveBeenCalledWith("endLoadingTestDefinition", backlog_item);
        });

        it("Marks the test definition as just refreshed if app wants to highlight it", async () => {
            tlpRecursiveGetMock.mockImplementation((route, config) => {
                config.getCollectionCallback([
                    { id: 123 },
                    { id: 124 },
                    { id: 1001 },
                ] as TestDefinition[]);
            });

            const backlog_item = { id: 101 } as BacklogItem;

            await loadTestDefinitions(context, backlog_item);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingTestDefinition", backlog_item);
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
                `/api/v1/backlog_items/101/test_definitions`,
                {
                    params: { milestone_id: 42, limit: 30 },
                    getCollectionCallback: expect.any(Function),
                },
            );
            expect(context.commit).toHaveBeenCalledWith("addTestDefinitions", {
                backlog_item,
                test_definitions: [
                    { id: 123, is_just_refreshed: false },
                    { id: 124, is_just_refreshed: false },
                    { id: 1001, is_just_refreshed: true },
                ] as TestDefinition[],
            });
            expect(context.commit).toHaveBeenCalledWith(
                "markTestDefinitionsAsBeingLoaded",
                backlog_item,
            );
            expect(context.commit).toHaveBeenCalledWith("endLoadingTestDefinition", backlog_item);
        });

        it("Catches error", async () => {
            const error = new Error();
            tlpRecursiveGetMock.mockRejectedValue(error);

            const backlog_item = { id: 101 } as BacklogItem;

            await expect(loadTestDefinitions(context, backlog_item)).rejects.toThrow();

            expect(context.commit).toHaveBeenCalledWith("beginLoadingTestDefinition", backlog_item);
            expect(context.commit).toHaveBeenCalledWith(
                "loadingErrorHasBeenCatchedForTestDefinition",
                backlog_item,
            );
            expect(context.commit).toHaveBeenCalledWith("endLoadingTestDefinition", backlog_item);
        });

        it("Does not catch 403 so that empty state can be displayed instead of error state", async () => {
            const error = new FetchWrapperError("Forbidden", { status: 403 } as Response);
            tlpRecursiveGetMock.mockRejectedValue(error);

            const backlog_item = { id: 101 } as BacklogItem;

            await loadTestDefinitions(context, backlog_item);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingTestDefinition", backlog_item);
            expect(context.commit).not.toHaveBeenCalledWith(
                "loadingErrorHasBeenCatchedForTestDefinition",
                backlog_item,
            );
            expect(context.commit).toHaveBeenCalledWith("endLoadingTestDefinition", backlog_item);
        });
    });
});
