import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("KanbanItemRestService -", function() {
    var mockBackend, KanbanItemRestService, response, RestErrorService;
    beforeEach(function() {
        angular.mock.module(kanban_module, function($provide) {
            $provide.decorator("RestErrorService", function($delegate) {
                spyOn($delegate, "reload");

                return $delegate;
            });
        });

        angular.mock.inject(function(_KanbanItemRestService_, _RestErrorService_, $httpBackend) {
            KanbanItemRestService = _KanbanItemRestService_;
            RestErrorService = _RestErrorService_;
            mockBackend = $httpBackend;
        });

        installPromiseMatchers();

        response = null;
    });

    afterEach(function() {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    describe("getItem()", function() {
        it("Given an item id, when I get the item, then a GET request will be made and a resolved promise will be returned", function() {
            response = {
                id: 410,
                item_name: "paterfamiliarly",
                label: "Disaccustomed"
            };
            mockBackend.expectGET("/api/v1/kanban_items/410").respond(JSON.stringify(response));

            var promise = KanbanItemRestService.getItem(410);
            mockBackend.flush();

            expect(promise).toBeResolvedWith(
                jasmine.objectContaining({
                    id: 410,
                    item_name: "paterfamiliarly",
                    label: "Disaccustomed"
                })
            );
        });

        it("When there is an error with my request, then the error will be handled by RestErrorService and a rejected promise will be returned", function() {
            mockBackend
                .expectGET("/api/v1/kanban_items/410")
                .respond(404, { error: 404, message: "Error" });

            var promise = KanbanItemRestService.getItem(410);

            expect(promise).toBeRejected();
            expect(RestErrorService.reload).toHaveBeenCalledWith(
                jasmine.objectContaining({
                    data: {
                        error: 404,
                        message: "Error"
                    }
                })
            );
        });
    });
});
