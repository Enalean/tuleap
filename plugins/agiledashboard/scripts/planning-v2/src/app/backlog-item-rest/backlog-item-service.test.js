/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import * as factory from "./backlog-item-factory";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

const expected_headers = { "content-type": "application/json" };

describe("BacklogItemService", () => {
    let $q, wrapPromise, BacklogItemService;

    beforeEach(() => {
        angular.mock.module(planning_module);

        let $rootScope;
        angular.mock.inject(function (_$rootScope_, _$q_, _BacklogItemService_) {
            $q = _$q_;
            $rootScope = _$rootScope_;
            BacklogItemService = _BacklogItemService_;
        });

        jest.spyOn(factory, "augment").mockImplementation((backlog_item) => backlog_item);

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            }),
        );
    }

    describe(`getBacklogItem`, () => {
        it(`will call GET on the backlog_item
            and will return it`, async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, {
                return_json: { id: 122, label: "A User Story" },
            });

            const promise = BacklogItemService.getBacklogItem(122);
            const response = await wrapPromise(promise);

            expect(response.backlog_item).toEqual(expect.objectContaining({ id: 122 }));
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/backlog_items/122");
        });
    });

    describe("getProjectBacklogItems()", () => {
        it(`will call GET on the project's backlog,
            will augment each received backlog item
            and will return the total number of items`, async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, {
                return_json: [{ id: 271 }, { id: 242 }],
                headers: {
                    get() {
                        return "2";
                    },
                },
            });

            const promise = BacklogItemService.getProjectBacklogItems(32, 50, 0);
            const response = await wrapPromise(promise);

            expect(response.results[0]).toEqual(expect.objectContaining({ id: 271 }));
            expect(response.results[1]).toEqual(expect.objectContaining({ id: 242 }));
            expect(response.total).toBe("2");
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/projects/32/backlog", {
                params: { limit: 50, offset: 0 },
            });
        });
    });

    describe("getMilestoneBacklogItems()", () => {
        it(`will call GET on the milestone's backlog,
            will augment each received backlog item
            and will return the total number of items`, async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, {
                return_json: [{ id: 398 }, { id: 848 }],
                headers: {
                    get() {
                        return "2";
                    },
                },
            });

            const promise = BacklogItemService.getMilestoneBacklogItems(65, 50, 0);
            const response = await wrapPromise(promise);

            expect(response.results[0]).toEqual(expect.objectContaining({ id: 398 }));
            expect(response.results[1]).toEqual(expect.objectContaining({ id: 848 }));
            expect(response.total).toBe("2");
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/milestones/65/backlog", {
                params: { limit: 50, offset: 0 },
            });
        });
    });

    describe("getBacklogItemChildren()", () => {
        it(`will call GET on the backlog item's children,
            will augment each received backlog item
            and will return the total number of items`, async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, {
                return_json: [{ id: 722 }, { id: 481 }],
                headers: {
                    get() {
                        return "2";
                    },
                },
            });

            const promise = BacklogItemService.getBacklogItemChildren(57, 50, 0);
            const response = await wrapPromise(promise);

            expect(response.results[0]).toEqual(expect.objectContaining({ id: 722 }));
            expect(response.results[1]).toEqual(expect.objectContaining({ id: 481 }));
            expect(response.total).toBe("2");
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/backlog_items/57/children", {
                params: { limit: 50, offset: 0 },
            });
        });
    });

    describe(`reorderBacklogItemChildren`, () => {
        it(`will call PATCH on the backlog item's children
            and reorder items`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = BacklogItemService.reorderBacklogItemChildren(307, [99, 187], {
                direction: "before",
                item_id: 265,
            });

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/backlog_items/307/children", {
                headers: expected_headers,
                body: JSON.stringify({
                    order: {
                        ids: [99, 187],
                        direction: "before",
                        compared_to: 265,
                    },
                }),
            });
        });
    });

    describe(`removeAddReorderBacklogItemChildren`, () => {
        it(`will call PATCH on the backlog item's children
            and reorder items while moving them from another backlog item`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = BacklogItemService.removeAddReorderBacklogItemChildren(
                122,
                307,
                [99, 187],
                {
                    direction: "after",
                    item_id: 265,
                },
            );

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/backlog_items/307/children", {
                headers: expected_headers,
                body: JSON.stringify({
                    order: {
                        ids: [99, 187],
                        direction: "after",
                        compared_to: 265,
                    },
                    add: [
                        { id: 99, remove_from: 122 },
                        { id: 187, remove_from: 122 },
                    ],
                }),
            });
        });
    });

    describe(`removeAddBacklogItemChildren`, () => {
        it(`will call PATCH on the backlog item's children
            and move items from another backlog item`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = BacklogItemService.removeAddBacklogItemChildren(122, 307, [99, 187]);

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/backlog_items/307/children", {
                headers: expected_headers,
                body: JSON.stringify({
                    add: [
                        { id: 99, remove_from: 122 },
                        { id: 187, remove_from: 122 },
                    ],
                }),
            });
        });
    });

    describe("removeItemFromExplicitBacklog()", () => {
        it(`Given a project id and an item id,
            then the PATCH route is called to remove the item id from explicit backlog`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);
            const project_id = 101;

            const promise = BacklogItemService.removeItemFromExplicitBacklog(project_id, [
                { id: 5 },
                { id: 54 },
            ]);

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/projects/101/backlog", {
                headers: expected_headers,
                body: JSON.stringify({
                    remove: [{ id: 5 }, { id: 54 }],
                }),
            });
        });
    });
});
