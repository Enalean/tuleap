describe("KanbanItemRestService -", function() {
    var mockBackend, KanbanItemRestService, response;
    beforeEach(function() {
        module('kanban');

        inject(function(_KanbanItemRestService_, $httpBackend) {
            KanbanItemRestService = _KanbanItemRestService_;
            mockBackend           = $httpBackend;
        });

        installPromiseMatchers();

        response = null;
    });

    afterEach(function() {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("getItem() - ", function() {
        response = {
            id       : 410,
            item_name: "paterfamiliarly",
            label    : "Disaccustomed"
        };
        mockBackend.expectGET('/api/v1/kanban_items/410').respond(JSON.stringify(response));

        var promise = KanbanItemRestService.getItem(410);
        mockBackend.flush();

        expect(promise).toBeResolvedWith(jasmine.objectContaining({
            id       : 410,
            item_name: "paterfamiliarly",
            label    : "Disaccustomed"
        }));
    });
});
