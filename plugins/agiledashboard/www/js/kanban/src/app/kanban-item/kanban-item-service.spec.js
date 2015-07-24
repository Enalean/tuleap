describe("KanbanItemService -", function() {
    var mockBackend, KanbanItemService, request, response;
    beforeEach(function() {
        module('kanban');

        inject(function(_KanbanItemService_, $httpBackend) {
            KanbanItemService = _KanbanItemService_;
            mockBackend = $httpBackend;
        });

        installPromiseMatchers();

        request = null;
        response = null;
    });

    afterEach(function() {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("getItem() - ", function() {
        response = {
            id: 410,
            item_name: "paterfamiliarly",
            label: "Disaccustomed"
        };
        mockBackend.expectGET('/api/v1/kanban_items/410').respond(JSON.stringify(response));

        var promise = KanbanItemService.getItem(410);
        mockBackend.flush();

        expect(promise).toBeResolvedWith(jasmine.objectContaining({
            id: 410,
            item_name: "paterfamiliarly",
            label: "Disaccustomed"
        }));
    });
});
