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
});
