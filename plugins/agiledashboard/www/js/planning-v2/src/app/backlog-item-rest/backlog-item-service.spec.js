import angular from "angular";
import "angular-mocks";

import rest_module from "./backlog-item-rest.js";

describe("BacklogItemService", function() {
    var $q, mockBackend, BacklogItemService, BacklogItemFactory, deferred;

    beforeEach(function() {
        BacklogItemFactory = jasmine.createSpyObj("BacklogItemFactory", ["augment"]);

        angular.mock.module(rest_module, function($provide) {
            $provide.value("BacklogItemFactory", BacklogItemFactory);
        });

        angular.mock.inject(function(_$q_, _BacklogItemService_, $httpBackend) {
            $q = _$q_;
            BacklogItemService = _BacklogItemService_;
            mockBackend = $httpBackend;
        });

        installPromiseMatchers();

        deferred = $q.defer();
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
});
