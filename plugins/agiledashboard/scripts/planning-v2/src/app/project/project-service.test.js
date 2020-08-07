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
import { createAngularPromiseWrapper } from "../../../../../../../tests/jest/angular-promise-wrapper";

describe(`ProjectService`, () => {
    let mockBackend, wrapPromise, ProjectService;

    beforeEach(() => {
        angular.mock.module(planning_module);

        let $rootScope;
        angular.mock.inject(function (_$rootScope_, _ProjectService_, $httpBackend) {
            $rootScope = _$rootScope_;
            ProjectService = _ProjectService_;
            mockBackend = $httpBackend;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(() => {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    describe(`reorderBacklog`, () => {
        it(`will call PATCH on the project backlog
            and reorder items`, async () => {
            mockBackend
                .expectPATCH("/api/v1/projects/103/backlog", {
                    order: {
                        ids: [99, 187],
                        direction: "before",
                        compared_to: 265,
                    },
                })
                .respond(200);

            const promise = ProjectService.reorderBacklog(103, [99, 187], {
                direction: "before",
                item_id: 265,
            });
            mockBackend.flush();

            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`removeAddReorderToBacklog`, () => {
        it(`will call PATCH on the project backlog
            and reorder items while moving them from another milestone`, async () => {
            mockBackend
                .expectPATCH("/api/v1/projects/103/backlog", {
                    order: {
                        ids: [99, 187],
                        direction: "after",
                        compared_to: 265,
                    },
                    add: [
                        { id: 99, remove_from: 77 },
                        { id: 187, remove_from: 77 },
                    ],
                })
                .respond(200);

            const promise = ProjectService.removeAddReorderToBacklog(77, 103, [99, 187], {
                direction: "after",
                item_id: 265,
            });
            mockBackend.flush();

            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`removeAddToBacklog`, () => {
        it(`will call PATCH on the project backlog
            and move items from another milestone`, async () => {
            mockBackend
                .expectPATCH("/api/v1/projects/103/backlog", {
                    add: [
                        { id: 99, remove_from: 77 },
                        { id: 187, remove_from: 77 },
                    ],
                })
                .respond(200);

            const promise = ProjectService.removeAddToBacklog(77, 103, [99, 187]);
            mockBackend.flush();

            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`getProject`, () => {
        it(`will call GET on the project`, async () => {
            mockBackend.expectGET("/api/v1/projects/103").respond(200, {
                id: 103,
                label: "Project",
            });

            const promise = ProjectService.getProject(103);
            mockBackend.flush();

            const response = await wrapPromise(promise);
            expect(response.data.id).toEqual(103);
            expect(response.data.label).toEqual("Project");
        });
    });

    describe(`getProjectBacklog`, () => {
        it(`will call GET on the project backlog
            and will format as string the accepted trackers`, async () => {
            mockBackend.expectGET("/api/v2/projects/103/backlog?limit=00&offset=0").respond(200, {
                accept: {
                    trackers: [
                        { id: 36, label: "User Stories" },
                        { id: 91, label: "Bugs" },
                    ],
                    parent_trackers: [{ id: 71, label: "Epics" }],
                },
                content: [],
                has_user_priority_change_permission: true,
            });

            const promise = ProjectService.getProjectBacklog(103);
            mockBackend.flush();

            const response = await wrapPromise(promise);
            expect(response.allowed_backlog_item_types.content).toContainEqual({
                id: 36,
                label: "User Stories",
            });
            expect(response.allowed_backlog_item_types.parent_trackers).toContainEqual({
                id: 71,
                label: "Epics",
            });
            expect(response.allowed_backlog_item_types.toString()).toEqual(
                "trackerId36|trackerId91"
            );
            expect(response.has_user_priority_change_permission).toBe(true);
        });
    });
});
