describe("BacklogItemCollectionService -", function() {
    var $q, $scope, BacklogItemCollectionService, BacklogItemService;

    beforeEach(function() {
        module('backlog-item-collection', function($provide) {
            $provide.decorator('BacklogItemService', function($delegate) {
                spyOn($delegate, "getBacklogItem");

                return $delegate;
            });
        });

        inject(function(
            _$q_,
            _$rootScope_,
            _BacklogItemCollectionService_,
            _BacklogItemService_
        ) {
            $q                           = _$q_;
            $scope                       = _$rootScope_.$new();
            BacklogItemCollectionService = _BacklogItemCollectionService_;
            BacklogItemService           = _BacklogItemService_;
        });

        installPromiseMatchers();
    });

    describe("refreshBacklogItem() -", function() {
        var get_backlog_item_request;

        beforeEach(function() {
            get_backlog_item_request = $q.defer();
        });

        describe("Given a backlog item's id and given that this item existed in the item collection", function() {
            var initial_item;

            beforeEach(function() {
                initial_item = {
                    id: 7088,
                    card_fields: [],
                    children: {
                        data: [],
                        collapsed: true,
                        loaded: true
                    },
                    has_children  : false,
                    initial_effort: 8,
                    label         : 'hexapod',
                    status        : 'Review',
                    updating      : false
                };

                BacklogItemCollectionService.items = {
                    7088: initial_item
                };
                BacklogItemService.getBacklogItem.and.returnValue(get_backlog_item_request.promise);
            });

            it("when I refresh it, then a promise will be resolved and the item will be fetched from the server and updated in the item collection", function() {
                var promise = BacklogItemCollectionService.refreshBacklogItem(7088);

                expect(BacklogItemCollectionService.items[7088].updating).toBeTruthy();
                get_backlog_item_request.resolve({
                    backlog_item: {
                        id: 7088,
                        card_fields: [
                            {
                                field_id: 35,
                                label: "Remaining Story Points",
                                type: "float",
                                value: 1.5
                            }
                        ],
                        has_children  : true,
                        initial_effort: 6,
                        label         : 'unspeedy',
                        status        : 'Closed'
                    }
                });

                expect(promise).toBeResolved();
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7088);
                expect(BacklogItemCollectionService.items[7088]).toBe(initial_item);
                expect(BacklogItemCollectionService.items[7088]).toEqual({
                    id: 7088,
                    card_fields: [
                        {
                            field_id: 35,
                            label: "Remaining Story Points",
                            type: "float",
                            value: 1.5
                        }
                    ],
                    children: {
                        data: [],
                        collapsed: true,
                        loaded: true
                    },
                    has_children  : true,
                    initial_effort: 6,
                    label         : 'unspeedy',
                    status        : 'Closed',
                    updating      : false
                });
            });
        });

    });
});
