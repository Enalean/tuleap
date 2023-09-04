/*
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
import * as factory from "../backlog-item-rest/backlog-item-factory";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

const expected_headers = { "content-type": "application/json" };

describe("MilestoneService", () => {
    let $q, wrapPromise, MilestoneService;

    beforeEach(() => {
        angular.mock.module(planning_module);

        let $rootScope;
        angular.mock.inject(function (_$rootScope_, _$q_, _MilestoneService_) {
            $q = _$q_;
            $rootScope = _$rootScope_;
            MilestoneService = _MilestoneService_;
        });

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

    describe(`getMilestone`, () => {
        let tlpGet;
        beforeEach(() => {
            tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, {
                return_json: {
                    id: 97,
                    label: "Release 1.5.4",
                    resources: {
                        backlog: {
                            accept: {
                                trackers: [
                                    { id: 36, label: "User Stories" },
                                    { id: 91, label: "Bugs" },
                                ],
                                parent_trackers: [{ id: 71, label: "Epics" }],
                            },
                        },
                        content: {
                            accept: {
                                trackers: [
                                    { id: 23, label: "Tasks" },
                                    { id: 78, label: "Activities" },
                                ],
                            },
                        },
                    },
                },
            });
        });

        it(`will call GET on the milestones
            and will format as string the accepted trackers
            and will augment the milestone object with the given scope items`, async () => {
            const promise = MilestoneService.getMilestone(97, []);

            const response = await wrapPromise(promise);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/milestones/97");

            const milestone = response.results;
            expect(milestone.initialEffort).toBe(0);
            expect(milestone.collapsed).toBe(true);
            expect(milestone.content).toEqual([]);
            expect(milestone.getContent).toBeDefined();
            expect(milestone.backlog_accepted_types.content).toContainEqual({
                id: 36,
                label: "User Stories",
            });
            expect(milestone.backlog_accepted_types.content).toContainEqual({
                id: 91,
                label: "Bugs",
            });
            expect(milestone.backlog_accepted_types.toString()).toBe("trackerId36|trackerId91");
            expect(milestone.content_accepted_types.content).toContainEqual({
                id: 23,
                label: "Tasks",
            });
            expect(milestone.content_accepted_types.content).toContainEqual({
                id: 78,
                label: "Activities",
            });
            expect(milestone.content_accepted_types.toString()).toBe("trackerId23|trackerId78");
        });

        it(`after getting the milestone, when I call getContent() on it,
            it will call GET on the milestone's content`, async () => {
            jest.spyOn(factory, "augment").mockImplementation((backlog_item) => backlog_item);
            const first_backlog_item = { id: 704, label: "First user Story", initial_effort: 1 };
            const second_backlog_item = { id: 999, label: "Second user Story", initial_effort: 3 };

            const scope_items = [];

            const promise = MilestoneService.getMilestone(97, scope_items);
            const milestone_response = await wrapPromise(promise);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/milestones/97");

            const milestone = milestone_response.results;
            mockFetchSuccess(tlpGet, {
                return_json: [first_backlog_item, second_backlog_item],
                headers: {
                    get: () => {
                        return "2";
                    },
                },
            });

            const second_promise = milestone.getContent();
            expect(milestone.loadingContent).toBe(true);
            expect(milestone.alreadyLoaded).toBe(true);

            await wrapPromise(second_promise);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/milestones/97/content", {
                params: { limit: 50, offset: 0 },
            });
            expect(scope_items[704]).toEqual(expect.objectContaining({ id: 704 }));
            expect(scope_items[999]).toEqual(expect.objectContaining({ id: 999 }));
            expect(milestone.content[0]).toEqual(expect.objectContaining({ id: 704 }));
            expect(milestone.content[1]).toEqual(expect.objectContaining({ id: 999 }));
            expect(milestone.loadingContent).toBe(false);
        });
    });

    describe(`getContent`, () => {
        it(`will call GET on the milestone's content
            and will return the X-PAGINATION-SIZE header as the total number of items`, async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            const first_backlog_item = { id: 140, label: "First User Story" };
            const second_backlog_item = { id: 142, label: "Second User Story" };
            mockFetchSuccess(tlpGet, {
                return_json: [first_backlog_item, second_backlog_item],
                headers: {
                    get() {
                        return "2";
                    },
                },
            });

            const promise = MilestoneService.getContent(25, 50, 0);
            const response = await wrapPromise(promise);

            expect(response.total).toBe("2");
            expect(response.results[0]).toEqual(expect.objectContaining({ id: 140 }));
            expect(response.results[1]).toEqual(expect.objectContaining({ id: 142 }));
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/milestones/25/content", {
                params: { limit: 50, offset: 0 },
            });
        });
    });

    describe(`patchSubMilestones`, () => {
        it(`will call PATCH on the milestone's milestones
            and add the new sub milestones`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = MilestoneService.patchSubMilestones(26, [77, 81]);

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/milestones/26/milestones", {
                headers: expected_headers,
                body: JSON.stringify({
                    add: [{ id: 77 }, { id: 81 }],
                }),
            });
        });
    });

    describe(`updateInitialEffort`, () => {
        it(`Sets milestone's initial effort as the sum of its backlog items' initial effort`, () => {
            const milestone = {
                initialEffort: 0,
                content: [{ initial_effort: 3 }, { initial_effort: 5 }],
            };
            MilestoneService.updateInitialEffort(milestone);
            expect(milestone.initialEffort).toBe(8);
        });
    });

    describe(`reorderBacklog`, () => {
        it(`will call PATCH on the milestone's backlog
            and reorder items`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = MilestoneService.reorderBacklog(26, [99, 187], {
                direction: "before",
                item_id: 265,
            });

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/milestones/26/backlog", {
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

    describe(`removeAddReorderToBacklog`, () => {
        it(`will call PATCH on the milestone's backlog
            and reorder items while moving them from another milestone`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = MilestoneService.removeAddReorderToBacklog(77, 26, [99, 187], {
                direction: "after",
                item_id: 265,
            });

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/milestones/26/backlog", {
                headers: expected_headers,
                body: JSON.stringify({
                    order: {
                        ids: [99, 187],
                        direction: "after",
                        compared_to: 265,
                    },
                    add: [
                        { id: 99, remove_from: 77 },
                        { id: 187, remove_from: 77 },
                    ],
                }),
            });
        });
    });

    describe(`removeAddToBacklog`, () => {
        it(`will call PATCH on the milestone's backlog
            and move items from another milestone`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = MilestoneService.removeAddToBacklog(77, 26, [99, 187]);

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/milestones/26/backlog", {
                headers: expected_headers,
                body: JSON.stringify({
                    add: [
                        { id: 99, remove_from: 77 },
                        { id: 187, remove_from: 77 },
                    ],
                }),
            });
        });
    });

    describe(`reorderContent`, () => {
        it(`will call PATCH on the milestone's content
            and reorder items`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = MilestoneService.reorderContent(26, [99, 187], {
                direction: "before",
                item_id: 265,
            });

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/milestones/26/content", {
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

    describe(`addReorderToContent`, () => {
        it(`will call PATCH on the milestone's content
            and add new items reordered`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = MilestoneService.addReorderToContent(26, [99, 187], {
                direction: "after",
                item_id: 265,
            });

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/milestones/26/content", {
                headers: expected_headers,
                body: JSON.stringify({
                    order: {
                        ids: [99, 187],
                        direction: "after",
                        compared_to: 265,
                    },
                    add: [{ id: 99 }, { id: 187 }],
                }),
            });
        });
    });

    describe(`addToContent`, () => {
        it(`will call PATCH on the milestone's content
            and add new items`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = MilestoneService.addToContent(26, [99, 187]);

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/milestones/26/content", {
                headers: expected_headers,
                body: JSON.stringify({ add: [{ id: 99 }, { id: 187 }] }),
            });
        });
    });

    describe(`removeAddReorderToContent`, () => {
        it(`will call PATCH on the milestone's content
            and reorder items while moving them from another milestone`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = MilestoneService.removeAddReorderToContent(77, 26, [99, 187], {
                direction: "after",
                item_id: 265,
            });

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/milestones/26/content", {
                headers: expected_headers,
                body: JSON.stringify({
                    order: {
                        ids: [99, 187],
                        direction: "after",
                        compared_to: 265,
                    },
                    add: [
                        { id: 99, remove_from: 77 },
                        { id: 187, remove_from: 77 },
                    ],
                }),
            });
        });
    });

    describe(`removeAddToContent`, () => {
        it(`will call PATCH on the milestone's content
            and move items from another milestone`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = MilestoneService.removeAddToContent(77, 26, [99, 187]);

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/milestones/26/content", {
                headers: expected_headers,
                body: JSON.stringify({
                    add: [
                        { id: 99, remove_from: 77 },
                        { id: 187, remove_from: 77 },
                    ],
                }),
            });
        });
    });
});
