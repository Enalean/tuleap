/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import planning_module from "../app";
import angular from "angular";
import "angular-mocks";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

const expected_headers = { "content-type": "application/json" };

describe(`ProjectService`, () => {
    let $q, wrapPromise, ProjectService;

    beforeEach(() => {
        angular.mock.module(planning_module);

        let $rootScope;
        angular.mock.inject(function (_$rootScope_, _$q_, _ProjectService_) {
            $q = _$q_;
            $rootScope = _$rootScope_;
            ProjectService = _ProjectService_;
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

    describe(`reorderBacklog`, () => {
        it(`will call PATCH on the project backlog
            and reorder items`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = ProjectService.reorderBacklog(103, [99, 187], {
                direction: "before",
                item_id: 265,
            });

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/projects/103/backlog", {
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
        it(`will call PATCH on the project backlog
            and reorder items while moving them from another milestone`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = ProjectService.removeAddReorderToBacklog(77, 103, [99, 187], {
                direction: "after",
                item_id: 265,
            });

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/projects/103/backlog", {
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
        it(`will call PATCH on the project backlog
            and move items from another milestone`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = ProjectService.removeAddToBacklog(77, 103, [99, 187]);

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/projects/103/backlog", {
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

    describe(`getProject`, () => {
        it(`will call GET on the project`, async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, {
                return_json: { id: 103, label: "Project" },
            });

            const promise = ProjectService.getProject(103);

            const response = await wrapPromise(promise);
            expect(response.data.id).toBe(103);
            expect(response.data.label).toBe("Project");
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/projects/103");
        });
    });

    describe(`getProjectBacklog`, () => {
        it(`will call GET on the project backlog
            and will format as string the accepted trackers`, async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, {
                return_json: {
                    accept: {
                        trackers: [
                            { id: 36, label: "User Stories" },
                            { id: 91, label: "Bugs" },
                        ],
                        parent_trackers: [{ id: 71, label: "Epics" }],
                    },
                    content: [],
                    has_user_priority_change_permission: true,
                },
            });

            const promise = ProjectService.getProjectBacklog(103);

            const response = await wrapPromise(promise);
            expect(response.allowed_backlog_item_types.content).toContainEqual({
                id: 36,
                label: "User Stories",
            });
            expect(response.allowed_backlog_item_types.parent_trackers).toContainEqual({
                id: 71,
                label: "Epics",
            });
            expect(response.allowed_backlog_item_types.toString()).toBe("trackerId36|trackerId91");
            expect(response.has_user_priority_change_permission).toBe(true);
            expect(tlpGet).toHaveBeenCalledWith("/api/v2/projects/103/backlog", {
                params: { limit: "00", offset: 0 },
            });
        });
    });
});
