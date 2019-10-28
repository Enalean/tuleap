import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("BacklogItemService", function() {
    var mockBackend, BacklogItemService, BacklogItemFactory;

    beforeEach(function() {
        BacklogItemFactory = jasmine.createSpyObj("BacklogItemFactory", ["augment"]);

        angular.mock.module(planning_module, function($provide) {
            $provide.value("BacklogItemFactory", BacklogItemFactory);
        });

        angular.mock.inject(function(_BacklogItemService_, $httpBackend) {
            BacklogItemService = _BacklogItemService_;
            mockBackend = $httpBackend;
        });

        installPromiseMatchers();
    });

    afterEach(function() {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    describe("getProjectBacklogItems() -", function() {
        it("Given a project id, a limit of 50 items and an offset of 0, when I get the project's backlog items, then a promise will be resolved with an object containing the items and the X-PAGINATION-SIZE header as the total number of items", function() {
            mockBackend
                .expectGET("/api/v1/projects/32/backlog?limit=50&offset=0")
                .respond([{ id: 271 }, { id: 242 }], {
                    "X-PAGINATION-SIZE": 2
                });

            var promise = BacklogItemService.getProjectBacklogItems(32, 50, 0);
            mockBackend.flush();

            expect(promise).toBeResolved();
            var value = promise.$$state.value;
            expect(value.results[0]).toEqual(jasmine.objectContaining({ id: 271 }));
            expect(value.results[1]).toEqual(jasmine.objectContaining({ id: 242 }));
            expect(value.total).toEqual("2");
        });
    });

    describe("getMilestoneBacklogItems() -", function() {
        it("Given a milestone id, a limit of 50 items and an offset of 0, when I get the milestone's backlog items, then a promise will be resolved with an object containing the items and the X-PAGINATION-SIZE header as the total number of items", function() {
            mockBackend
                .expectGET("/api/v1/milestones/65/backlog?limit=50&offset=0")
                .respond([{ id: 398 }, { id: 848 }], {
                    "X-PAGINATION-SIZE": 2
                });

            var promise = BacklogItemService.getMilestoneBacklogItems(65, 50, 0);
            mockBackend.flush();

            expect(promise).toBeResolved();
            var value = promise.$$state.value;
            expect(value.results[0]).toEqual(jasmine.objectContaining({ id: 398 }));
            expect(value.results[1]).toEqual(jasmine.objectContaining({ id: 848 }));
            expect(value.total).toEqual("2");
        });
    });

    describe("getBacklogItemChildren() -", function() {
        it("Given a backlog item id, a limit of 50 items and an offset of 0, when I get the backlog item's  children, then a promise will be resolved with an object containing the children and the X-PAGINATION-SIZE header as the total number of children", function() {
            mockBackend
                .expectGET("/api/v1/backlog_items/57/children?limit=50&offset=0")
                .respond([{ id: 722 }, { id: 481 }], {
                    "X-PAGINATION-SIZE": 2
                });

            var promise = BacklogItemService.getBacklogItemChildren(57, 50, 0);
            mockBackend.flush();

            expect(promise).toBeResolved();
            var value = promise.$$state.value;
            expect(value.results[0]).toEqual(jasmine.objectContaining({ id: 722 }));
            expect(value.results[1]).toEqual(jasmine.objectContaining({ id: 481 }));
            expect(value.total).toEqual("2");
        });
    });

    describe("removeItemFromExplicitBacklog() -", function() {
        it("Given a project id and an item id, then the PATCH route is called to remove the item id from explicit backlog", function() {
            const project_id = 101;
            const backlog_item = { id: 5 };
            mockBackend
                .expectPATCH("/api/v1/projects/" + project_id + "/backlog", {
                    remove: [{ id: backlog_item.id }]
                })
                .respond(200);

            var promise = BacklogItemService.removeItemFromExplicitBacklog(project_id, [
                backlog_item
            ]);
            mockBackend.flush();

            expect(promise).toBeResolved();
        });
    });
});
