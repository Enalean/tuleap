describe("BacklogItemDetailsController -", function() {
    var $q, $scope, BacklogItemDetailsController, BacklogItemCollectionService,
        NewTuleapArtifactModalService, BacklogItemService;

    beforeEach(function() {
        module('backlog-item-details');

        inject(function(
            _$q_,
            $rootScope,
            $controller,
            _BacklogItemCollectionService_,
            _BacklogItemService_,
            _NewTuleapArtifactModalService_
        ) {
            $q     = _$q_;
            $scope = $rootScope.$new();

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            spyOn(BacklogItemCollectionService, 'refreshBacklogItem');

            BacklogItemService = _BacklogItemService_;
            spyOn(BacklogItemService, "getBacklogItem");
            spyOn(BacklogItemService, "getBacklogItemChildren");
            spyOn(BacklogItemService, "removeAddBacklogItemChildren");

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            spyOn(NewTuleapArtifactModalService, 'showCreation');

            BacklogItemDetailsController = $controller('BacklogItemDetailsController', {
                BacklogItemCollectionService : BacklogItemCollectionService,
                BacklogItemService           : BacklogItemService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService
            });
        });
    });

    describe("showAddChildModal() -", function() {
        var event, item_type;
        beforeEach(function() {
            event       = jasmine.createSpyObj("Click event", ["preventDefault"]);
            item_type   = { id: 7 };
            BacklogItemDetailsController.backlog_item = {
                id          : 53,
                has_children: true,
                children    : {
                    loaded: true,
                    data  : [
                        { id: 352 }
                    ]
                },
                updating: false
            };
            BacklogItemCollectionService.items[53] = BacklogItemDetailsController.backlog_item;
        });

        it("Given an event and an item type, when I show the modal to add a child to the current backlog item, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", function() {
            BacklogItemDetailsController.showAddChildModal(event, item_type);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                7,
                BacklogItemDetailsController.backlog_item,
                jasmine.any(Function)
            );
        });

        describe("callback -", function() {
            var artifact, get_backlog_item_request, remove_add_backlog_item_children_request;
            beforeEach(function() {
                get_backlog_item_request                 = $q.defer();
                remove_add_backlog_item_children_request = $q.defer();
                NewTuleapArtifactModalService.showCreation.and.callFake(function(a, b, callback) {
                    callback(207);
                });
                BacklogItemService.getBacklogItem.and.returnValue(get_backlog_item_request.promise);
                artifact = {
                    backlog_item: {
                        id: 207
                    }
                };
                BacklogItemService.removeAddBacklogItemChildren.and.returnValue(remove_add_backlog_item_children_request.promise);
            });

            it("When the new artifact modal calls its callback, then the artifact will be appended to the current backlog item's children using REST, it will be retrieved from the server, added to the items collection and appended to the current backlog item's children array", function() {
                BacklogItemDetailsController.showAddChildModal(event, item_type);
                get_backlog_item_request.resolve(artifact);
                remove_add_backlog_item_children_request.resolve();
                $scope.$apply();

                expect(BacklogItemService.removeAddBacklogItemChildren).toHaveBeenCalledWith(
                    undefined,
                    53,
                    [207]
                );
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(207);
                expect(BacklogItemCollectionService.items[207]).toEqual({ id: 207 });
                expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(53);
                expect(BacklogItemDetailsController.backlog_item.children.data).toEqual([
                    { id: 352 },
                    { id: 207 }
                ]);
            });

            it("Given that the current backlog item did not have children, when the new artifact modal calls its callback, then the artifact will be appended to the current backlog item's children and the children will be marked as loaded", function() {
                BacklogItemDetailsController.backlog_item.children = {
                    loaded: false,
                    data  : []
                };
                BacklogItemDetailsController.backlog_item.has_children = false;

                BacklogItemDetailsController.showAddChildModal(event, item_type);
                get_backlog_item_request.resolve(artifact);
                remove_add_backlog_item_children_request.resolve();
                $scope.$apply();

                expect(BacklogItemDetailsController.backlog_item.children.data).toEqual([
                    { id: 207 }
                ]);
                expect(BacklogItemDetailsController.backlog_item.children.loaded).toBeTruthy();
            });
        });
    });

    describe("canBeAddedToBacklogItemChildren() - ", function() {
        it("Given that the current backlog item had no child, it appends the newly created child", function() {
            BacklogItemDetailsController.backlog_item = {
                has_children: false,
                children    : {}
            };
            var created_item = {
                id: 8
            };

            var result = BacklogItemDetailsController.canBeAddedToChildren(created_item.id);

            expect(result).toBeTruthy();
        });

        it("Given that the current backlog item had already loaded children, it appends the newly created child if not already present", function() {
            BacklogItemDetailsController.backlog_item = {
                has_children: true,
                children    : {
                    loaded: true,
                    data  : [
                        { id: 1 },
                        { id: 2 },
                        { id: 3 }
                    ]
                }
            };
            var created_item = {
                id: 8
            };

            var result = BacklogItemDetailsController.canBeAddedToChildren(created_item.id);

            expect(result).toBeTruthy();
        });

        it("Given that the current backlog item had already loaded children, it doesn't append the newly created child if already present", function() {
            BacklogItemDetailsController.backlog_item = {
                has_children: true,
                children    : {
                    loaded: true,
                    data  : [
                        { id: 1 },
                        { id: 2 },
                        { id: 8 }
                    ]
                }
            };
            var created_item = {
                id: 8
            };

            var result = BacklogItemDetailsController.canBeAddedToChildren(created_item.id);

            expect(result).toBeFalsy();
        });

        it("Given that the current backlog item didn't have already loaded children, it doesn't append the newly created child", function() {
            BacklogItemDetailsController.backlog_item = {
                has_children: true,
                children    : {
                    loaded: false,
                    data  : []
                }
            };
            var created_item = {
                id: 8
            };

            expect(BacklogItemDetailsController.canBeAddedToChildren(created_item.id)).toBeFalsy();
        });
    });
});
