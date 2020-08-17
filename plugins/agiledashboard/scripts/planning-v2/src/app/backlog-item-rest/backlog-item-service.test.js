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
import { createAngularPromiseWrapper } from "../../../../../../../tests/jest/angular-promise-wrapper.js";

describe("BacklogItemService", () => {
    let mockBackend, wrapPromise, BacklogItemService, BacklogItemFactory;

    beforeEach(() => {
        BacklogItemFactory = { augment: jest.fn() };

        angular.mock.module(planning_module, function ($provide) {
            $provide.value("BacklogItemFactory", BacklogItemFactory);
        });

        let $rootScope;
        angular.mock.inject(function (_$rootScope_, _BacklogItemService_, $httpBackend) {
            $rootScope = _$rootScope_;
            BacklogItemService = _BacklogItemService_;
            mockBackend = $httpBackend;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(() => {
        mockBackend.verifyNoOutstandingExpectation(false); // We already trigger $digest
        mockBackend.verifyNoOutstandingRequest(false); // We already trigger $digest
    });

    describe(`getBacklogItem`, () => {
        it(`will call GET on the backlog_item
            and will return it`, async () => {
            mockBackend
                .expectGET("/api/v1/backlog_items/122")
                .respond({ id: 122, label: "A User Story" });

            const promise = BacklogItemService.getBacklogItem(122);
            mockBackend.flush();

            const response = await wrapPromise(promise);
            expect(response.backlog_item).toEqual(expect.objectContaining({ id: 122 }));
        });
    });

    describe("getProjectBacklogItems()", () => {
        it(`Given a project id, a limit of 50 items and an offset of 0,
            when I get the project's backlog items,
            then a promise will be resolved with an object containing the items
            and the X-PAGINATION-SIZE header as the total number of items`, async () => {
            mockBackend
                .expectGET("/api/v1/projects/32/backlog?limit=50&offset=0")
                .respond([{ id: 271 }, { id: 242 }], {
                    "X-PAGINATION-SIZE": 2,
                });

            const promise = BacklogItemService.getProjectBacklogItems(32, 50, 0);
            mockBackend.flush();

            await wrapPromise(promise);
            var value = promise.$$state.value;
            expect(value.results[0]).toEqual(expect.objectContaining({ id: 271 }));
            expect(value.results[1]).toEqual(expect.objectContaining({ id: 242 }));
            expect(value.total).toEqual("2");
        });
    });

    describe("getMilestoneBacklogItems()", () => {
        it(`Given a milestone id, a limit of 50 items and an offset of 0,
            when I get the milestone's backlog items,
            then a promise will be resolved with an object containing the items
            and the X-PAGINATION-SIZE header as the total number of items`, async () => {
            mockBackend
                .expectGET("/api/v1/milestones/65/backlog?limit=50&offset=0")
                .respond([{ id: 398 }, { id: 848 }], {
                    "X-PAGINATION-SIZE": 2,
                });

            const promise = BacklogItemService.getMilestoneBacklogItems(65, 50, 0);
            mockBackend.flush();

            await wrapPromise(promise);
            var value = promise.$$state.value;
            expect(value.results[0]).toEqual(expect.objectContaining({ id: 398 }));
            expect(value.results[1]).toEqual(expect.objectContaining({ id: 848 }));
            expect(value.total).toEqual("2");
        });
    });

    describe("getBacklogItemChildren()", () => {
        it(`Given a backlog item id, a limit of 50 items and an offset of 0,
            when I get the backlog item's children,
            then a promise will be resolved with an object containing the children
            and the X-PAGINATION-SIZE header as the total number of children`, async () => {
            mockBackend
                .expectGET("/api/v1/backlog_items/57/children?limit=50&offset=0")
                .respond([{ id: 722 }, { id: 481 }], {
                    "X-PAGINATION-SIZE": 2,
                });

            const promise = BacklogItemService.getBacklogItemChildren(57, 50, 0);
            mockBackend.flush();

            await wrapPromise(promise);
            var value = promise.$$state.value;
            expect(value.results[0]).toEqual(expect.objectContaining({ id: 722 }));
            expect(value.results[1]).toEqual(expect.objectContaining({ id: 481 }));
            expect(value.total).toEqual("2");
        });
    });

    describe("removeItemFromExplicitBacklog()", () => {
        it(`Given a project id and an item id,
            then the PATCH route is called to remove the item id from explicit backlog`, () => {
            const project_id = 101;
            const backlog_item = { id: 5 };
            mockBackend
                .expectPATCH(`/api/v1/projects/${project_id}/backlog`, {
                    remove: [{ id: backlog_item.id }],
                })
                .respond(200);

            const promise = BacklogItemService.removeItemFromExplicitBacklog(project_id, [
                backlog_item,
            ]);
            mockBackend.flush();

            return wrapPromise(promise);
        });
    });

    describe(`reorderBacklogItemChildren`, () => {
        it(`will call PATCH on the backlog item's children
            and reorder items`, async () => {
            mockBackend
                .expectPATCH("/api/v1/backlog_items/307/children", {
                    order: {
                        ids: [99, 187],
                        direction: "before",
                        compared_to: 265,
                    },
                })
                .respond(200);

            const promise = BacklogItemService.reorderBacklogItemChildren(307, [99, 187], {
                direction: "before",
                item_id: 265,
            });
            mockBackend.flush();

            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`removeAddReorderBacklogItemChildren`, () => {
        it(`will call PATCH on the backlog item's children
            and reorder items while moving them from another backlog item`, async () => {
            mockBackend
                .expectPATCH("/api/v1/backlog_items/307/children", {
                    order: {
                        ids: [99, 187],
                        direction: "after",
                        compared_to: 265,
                    },
                    add: [
                        { id: 99, remove_from: 122 },
                        { id: 187, remove_from: 122 },
                    ],
                })
                .respond(200);

            const promise = BacklogItemService.removeAddReorderBacklogItemChildren(
                122,
                307,
                [99, 187],
                {
                    direction: "after",
                    item_id: 265,
                }
            );
            mockBackend.flush();

            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`removeAddBacklogItemChildren`, () => {
        it(`will call PATCH on the backlog item's children
            and move items from another backlog item`, async () => {
            mockBackend
                .expectPATCH("/api/v1/backlog_items/307/children", {
                    add: [
                        { id: 99, remove_from: 122 },
                        { id: 187, remove_from: 122 },
                    ],
                })
                .respond(200);

            const promise = BacklogItemService.removeAddBacklogItemChildren(122, 307, [99, 187]);
            mockBackend.flush();

            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });
});
