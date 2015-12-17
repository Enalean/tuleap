describe("BacklogItemController -", function() {
    var $q, $scope, BacklogItemController, BacklogItemService, CardFieldsService,
        BacklogItemCollectionService, NewTuleapArtifactModalService;

    beforeEach(function() {
        module('backlog');

        inject(function(
            _$q_,
            $rootScope,
            $controller,
            _BacklogItemService_,
            _CardFieldsService_,
            _BacklogItemCollectionService_,
            _NewTuleapArtifactModalService_
        ) {
            $q       = _$q_;
            $scope   = $rootScope.$new();

            BacklogItemService = _BacklogItemService_;
            spyOn(BacklogItemService, "getBacklogItemChildren");
            spyOn(BacklogItemService, "getBacklogItem");
            spyOn(BacklogItemService, "removeAddBacklogItemChildren");

            CardFieldsService = _CardFieldsService_;

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            spyOn(BacklogItemCollectionService, 'refreshBacklogItem');

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            spyOn(NewTuleapArtifactModalService, 'showCreation');

            BacklogItemController = $controller('BacklogItemController', {
                $scope                       : $scope,
                BacklogItemService           : BacklogItemService,
                CardFieldsService            : CardFieldsService,
                BacklogItemCollectionService : BacklogItemCollectionService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService
            });
        });
    });

    describe("toggleChildrenDisplayed() -", function() {
        var fake_scope, backlog_item, get_backlog_item_request;

        beforeEach(function() {
            fake_scope = jasmine.createSpyObj("scope", ["toggle"]);
            get_backlog_item_request = $q.defer();
            BacklogItemService.getBacklogItemChildren.and.returnValue(get_backlog_item_request.promise);
        });

        describe("Given a scope and a backlog item", function() {
            it("with children that were not already loaded, when I show its children, then the scope will be toggled and the item's children will be loaded", function() {
                backlog_item = {
                    id: 352,
                    has_children: true,
                    children: {
                        loaded: false
                    }
                };

                BacklogItemController.toggleChildrenDisplayed(fake_scope, backlog_item);

                expect(fake_scope.toggle).toHaveBeenCalled();
                expect(BacklogItemService.getBacklogItemChildren).toHaveBeenCalledWith(352, 50, 0);
            });

            it("with no children, when I show its children, then the scope will be toggled and BacklogItemService won't be called", function() {
                backlog_item = {
                    has_children: false
                };

                BacklogItemController.toggleChildrenDisplayed(fake_scope, backlog_item);

                expect(fake_scope.toggle).toHaveBeenCalled();
                expect(BacklogItemService.getBacklogItemChildren).not.toHaveBeenCalled();
            });

            it("with children that were already loaded, when I show its children, then the scope will be toggled and BacklogItemService won't be called", function() {
                backlog_item = {
                    has_children: true,
                    children: {
                        loaded: true
                    }
                };

                BacklogItemController.toggleChildrenDisplayed(fake_scope, backlog_item);

                expect(fake_scope.toggle).toHaveBeenCalled();
                expect(BacklogItemService.getBacklogItemChildren).not.toHaveBeenCalled();
            });
        });
    });

    describe("showAddChildModal() -", function() {
        var event, item_type, parent_item;
        beforeEach(function() {
            event       = jasmine.createSpyObj("Click event", ["preventDefault"]);
            item_type   = { id: 7 };
            parent_item = {
                id: 53,
                has_children: true,
                children: {
                    loaded: true,
                    data: [
                        { id: 352 }
                    ]
                },
                updating: false
            };
            BacklogItemCollectionService.items[53] = parent_item;
        });

        it("Given an event, an item type and a parent item, when I show the modal to add a child to an item, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", function() {
            BacklogItemController.showAddChildModal(event, item_type, parent_item);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(7, parent_item, jasmine.any(Function));
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

            it("When the new artifact modal calls its callback, then the artifact will be appended to the parent item's children using REST, it will be retrieved from the server, added to the items collection and appended to the parent's children array", function() {
                BacklogItemController.showAddChildModal(event, item_type, parent_item);
                get_backlog_item_request.resolve(artifact);
                remove_add_backlog_item_children_request.resolve();
                $scope.$apply();

                expect(BacklogItemService.removeAddBacklogItemChildren).toHaveBeenCalledWith(undefined, 53, 207);
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(207);
                expect(BacklogItemCollectionService.items[207]).toEqual({ id: 207 });
                expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(53);
                expect(parent_item.children.data).toEqual([
                    { id: 352 },
                    { id: 207 }
                ]);
            });

            it("Given a parent item that did not have children, when the new artifact modal calls its callback, then the artifact will be appended to the parent item's children and the children will be marjed as loaded", function() {
                parent_item.children = {
                    loaded: false,
                    data: []
                };
                parent_item.has_children = false;

                BacklogItemController.showAddChildModal(event, item_type, parent_item);
                get_backlog_item_request.resolve(artifact);
                remove_add_backlog_item_children_request.resolve();
                $scope.$apply();

                expect(parent_item.children.data).toEqual([
                    { id: 207 }
                ]);
                expect(parent_item.children.loaded).toBeTruthy();
            });
        });
    });

    describe("canBeAddedToBacklogItemChildren() - ", function() {
        it("Given a parent with no child, it appends the newly created child", function() {
            var parent = {
                has_children: false,
                children    : {}
            };
            var created_item = {
                id: 8
            };

            var result = BacklogItemController.canBeAddedToBacklogItemChildren(created_item.id, parent);

            expect(result).toBeTruthy();
        });

        it("Given a parent with already loaded children, it appends the newly created child if not already present", function() {
            var parent = {
                has_children: true,
                children    : {
                    loaded: true,
                    data: [
                        { id: 1 },
                        { id: 2 },
                        { id: 3 }
                    ]
                }
            };
            var created_item = {
                id: 8
            };

            var result = BacklogItemController.canBeAddedToBacklogItemChildren(created_item.id, parent);

            expect(result).toBeTruthy();
        });

        it("Given a parent with already loaded children, it doesn't append the newly created child if already present", function() {
            var parent = {
                has_children: true,
                children    : {
                    loaded: true,
                    data: [
                        { id: 1 },
                        { id: 2 },
                        { id: 8 }
                    ]
                }
            };
            var created_item = {
                id: 8
            };

            var result = BacklogItemController.canBeAddedToBacklogItemChildren(created_item.id, parent);

            expect(result).toBeFalsy();
        });

        it("Given a parent with not already loaded children, it doesn't append the newly created child", function() {
            var parent = {
                has_children: true,
                children    : {
                    loaded: false,
                    children: []
                }
            };
            var created_item = {
                id: 8
            };

            expect(BacklogItemController.canBeAddedToBacklogItemChildren(created_item.id, parent)).toBeFalsy();
        });
    });
});
