describe("BacklogItemController -", function() {
    var $q, $rootScope, $scope, $compile, $document, $element, BacklogItemController,
        BacklogItemService, BacklogItemSelectedService, CardFieldsService, DroppedService, BacklogItemCollectionService,
        NewTuleapArtifactModalService, dragularService;

    beforeEach(function() {
        module('backlog');

        inject(function(
            _$q_,
            _$rootScope_,
            $controller,
            _$document_,
            _$compile_,
            _BacklogItemService_,
            _BacklogItemSelectedService_,
            _dragularService_,
            _DroppedService_,
            _CardFieldsService_,
            _BacklogItemCollectionService_,
            _NewTuleapArtifactModalService_
        ) {
            $q         = _$q_;
            $rootScope = _$rootScope_;
            $scope     = $rootScope.$new();
            $scope.backlog_item = {
                id: 49348548,
                children: {
                    data: [],
                    loaded: false,
                    collapsed: false
                }
            };
            $compile  = _$compile_;
            $document = _$document_;

            var current_backlog_item = $('<backlog-item>').appendTo('body');
            $element                 = angular.element(current_backlog_item);

            BacklogItemService = _BacklogItemService_;
            spyOn(BacklogItemService, "getBacklogItemChildren");
            spyOn(BacklogItemService, "getBacklogItem");
            spyOn(BacklogItemService, "removeAddBacklogItemChildren");

            DroppedService = _DroppedService_;
            spyOn(DroppedService, "reorderBacklogItemChildren");
            spyOn(DroppedService, "moveFromChildrenToChildren");
            spyOn(DroppedService, 'defineComparedToBeFirstItem').and.callThrough();
            spyOn(DroppedService, 'defineComparedToBeLastItem').and.callThrough();

            CardFieldsService = _CardFieldsService_;

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            spyOn(BacklogItemCollectionService, 'refreshBacklogItem');
            spyOn(BacklogItemCollectionService, 'addOrReorderBacklogItemsInCollection');

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            spyOn(NewTuleapArtifactModalService, 'showCreation');

            dragularService = _dragularService_;

            BacklogItemSelectedService = _BacklogItemSelectedService_;
            spyOn(BacklogItemSelectedService, 'areThereMultipleSelectedBaklogItems');
            spyOn(BacklogItemSelectedService, 'getCompactedSelectedBacklogItem');

            BacklogItemController = $controller('BacklogItemController', {
                $scope                       : $scope,
                $element                     : $element,
                $document                    : $document,
                BacklogItemService           : BacklogItemService,
                dragularService              : dragularService,
                DroppedService               : DroppedService,
                CardFieldsService            : CardFieldsService,
                BacklogItemCollectionService : BacklogItemCollectionService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService,
                BacklogItemSelectedService   : BacklogItemSelectedService
            });
        });
    });

    afterEach(function() {
        $('<backlog-item>').remove();
    });

    describe("toggleChildrenDisplayed() -", function() {
        var backlog_item, get_backlog_item_children_request;

        beforeEach(function() {
            get_backlog_item_children_request = $q.defer();
            BacklogItemService.getBacklogItemChildren.and.returnValue(get_backlog_item_children_request.promise);
        });

        describe("Given a backlog item", function() {
            it("with children that were not already loaded, when I show its children, then the item's children will be loaded and un-collapsed", function() {
                BacklogItemController.backlog_item = {
                    id: 352,
                    has_children: true,
                    children: {
                        collapsed: true,
                        data     : [],
                        loaded   : false
                    }
                };

                BacklogItemController.toggleChildrenDisplayed();
                expect(BacklogItemController.backlog_item.loading).toBeTruthy();
                get_backlog_item_children_request.resolve({
                    results: [
                        { id: 151 },
                        { id: 857 }
                    ],
                    total: 2
                });
                $scope.$apply();

                expect(BacklogItemService.getBacklogItemChildren).toHaveBeenCalledWith(352, 50, 0);
                expect(BacklogItemController.backlog_item.loading).toBeFalsy();
                expect(BacklogItemController.backlog_item.children.collapsed).toBeFalsy();
                expect(BacklogItemController.backlog_item.children.loaded).toBeTruthy();
                expect(BacklogItemController.backlog_item.children.data).toEqual([
                    { id: 151 },
                    { id: 857 }
                ]);
            });

            it("with no children, when I show its children, then BacklogItemService won't be called", function() {
                BacklogItemController.backlog_item = {
                    has_children: false,
                    children: {
                        collapsed: true
                    }
                };

                BacklogItemController.toggleChildrenDisplayed();

                expect(BacklogItemService.getBacklogItemChildren).not.toHaveBeenCalled();
                expect(BacklogItemController.backlog_item.loading).toBeFalsy();
                expect(BacklogItemController.backlog_item.children.collapsed).toBeTruthy();
            });

            it("with children that were already loaded and collapsed, when I show its children, then BacklogItemService won't be called and the item's children will be un-collapsed", function() {
                BacklogItemController.backlog_item = {
                    has_children: true,
                    children: {
                        collapsed: true,
                        loaded: true
                    }
                };

                BacklogItemController.toggleChildrenDisplayed();

                expect(BacklogItemService.getBacklogItemChildren).not.toHaveBeenCalled();
                expect(BacklogItemController.backlog_item.children.collapsed).toBeFalsy();
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

                expect(BacklogItemService.removeAddBacklogItemChildren).toHaveBeenCalledWith(undefined, 53, [207]);
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

    describe("dragularEnter() -", function() {
        var $dropped_item_element, dropped_item_ids, dropped_items, $source_element,
            $target_list_element, source_backlog_item_id, target_backlog_item_id;

        beforeEach(function() {
            dropped_item_ids       = [18];
            dropped_items          = [{id: 18}];
            source_backlog_item_id = 57;
            target_backlog_item_id = 64;
            $dropped_item_element  = affix('li');
            angular.element($dropped_item_element).data('item-id', dropped_item_ids);
            angular.element($dropped_item_element).data('type', 'trackerId24');
            dragularService.shared.item   = $dropped_item_element;
            $source_element               = affix('ul.backlog-item-children');
            angular.element($source_element).data('backlog-item-id', source_backlog_item_id);
            dragularService.shared.source = $source_element;
            spyOn($element, "addClass");

            $target_list_element = $element.affix('ul.backlog-item-children');
            $target_list_element = angular.element($target_list_element);
            $target_list_element.data('backlog-item-id', target_backlog_item_id);

            BacklogItemController.initDragularForBacklogItemChildren();
        });

        describe("Given I was dragging a child (e.g. a Task) and given a backlog item (e.g. a User Story)", function() {
            it("and given I can drop the child on it, when I drag it over the backlog item, then the 'appending-child' css class will be added to the current $element", function() {
                $target_list_element.data('accept', 'trackerId24|trackerId80');

                $element.trigger('dragularenter');

                expect($element.addClass).toHaveBeenCalledWith('appending-child');
            });

            it("and given I can't drop the child on it, when I drag it over the backlog item, then the 'appending-child' css class won't be added to the current $element", function() {
                $target_list_element.data('accept', 'trackerId80');

                $element.trigger('dragularenter');

                expect($element.addClass).not.toHaveBeenCalled();
            });

            it("when I drag the child over its current parent (target == source), then the 'appending-child' css class wont't be added to the current $element", function() {
                $source_element               = $element;
                dragularService.shared.source = $source_element;
                $target_list_element.data('accept', '');

                $element.trigger('dragularenter');

                expect($element.addClass).not.toHaveBeenCalled();
            });
        });
    });

    describe("dragularLeave() -", function() {
        it("Given I was dragging something, when I leave a backlog item, then the 'appending-child' css class will be removed from the current $element", function() {
            BacklogItemController.initDragularForBacklogItemChildren();

            spyOn($element, "removeClass");

            $element.trigger('dragularleave');

            expect($element.removeClass).toHaveBeenCalledWith('appending-child');
        });
    });

    describe("dragularRelease() -", function() {
        it("Given I was dragging something, when I release the item I was dragging, then the 'appending-child' css class will be removed from the current $element", function() {
            BacklogItemController.initDragularForBacklogItemChildren();

            spyOn($element, "removeClass");

            $element.trigger('dragularrelease');

            expect($element.removeClass).toHaveBeenCalledWith('appending-child');
        });
    });

    describe("dragularCancel() -", function() {
        describe("Given an event, the dropped element, the source element, the target element and the initial index", function() {
            var $dropped_item_element, dropped_item_ids, dropped_items, $target_element,
                $source_element, $backlog_item_element, $target_list_element,
                source_backlog_item, target_backlog_item, initial_index, compared_to,
                move_request;

            beforeEach(function() {
                dropped_item_ids = [60];
                dropped_items    = [{id: 60}];
                $dropped_item_element = affix('li');
                angular.element($dropped_item_element).data('item-id', dropped_item_ids[0]);
                angular.element($dropped_item_element).data('type', 'trackerId70');
                source_backlog_item = {
                    id          : 87,
                    updating    : false,
                    has_children: true,
                    children: {
                        collapsed: false,
                        data: [
                            { id: dropped_item_ids[0] }
                        ]
                    }
                };
                $source_element     = affix('ul.backlog-item-children');
                angular.element($source_element).data('backlog-item-id', source_backlog_item.id);
                $source_element.append($dropped_item_element);
                BacklogItemController.backlog_item = source_backlog_item;
                target_backlog_item = {
                    id: 51,
                    updating: false,
                    has_children: true,
                    children: {
                        collapsed: true,
                        data: [
                            { id: 25 }
                        ]
                    }
                };
                initial_index = 0;
                dragularService.shared.initialIndex = initial_index;
                compared_to   = {
                    direction: 'before',
                    item_id: 25
                };
                BacklogItemCollectionService.items[source_backlog_item.id] = source_backlog_item;
                BacklogItemCollectionService.items[target_backlog_item.id] = target_backlog_item;

                BacklogItemController.initDragularForBacklogItemChildren();

                move_request = $q.defer();
                DroppedService.moveFromChildrenToChildren.and.returnValue(move_request.promise);
            });

            describe("and given the target element was a descendant of a backlog-item element that had a list of children", function() {
                beforeEach(function() {
                    $backlog_item_element        = $('<backlog-item>').appendTo('body');
                    $target_list_element         = $backlog_item_element.affix('ul.backlog-item-children');
                    $target_list_element         = angular.element($target_list_element);
                    $target_list_element.data('backlog-item-id', target_backlog_item.id);
                    $target_element              = $backlog_item_element.affix('div');
                    var target_scope             = $rootScope.$new();
                    target_scope.backlog_item    = target_backlog_item;
                    $compile($target_list_element)(target_scope);
                    dragularService.shared.extra = $target_element;
                });

                afterEach(function() {
                    $('<backlog-item>').remove();
                });

                it("and given I can drop into the target element, when I cancel the drop of a child (e.g. a Task) over an item (e.g. a User Story), then the dropped element will be removed from the source element, the child will be removed from the source backlog item's model and prepended to the target backlog item's model, the target backlog item will be marked as having children, the child will be moved using DroppedService and both the source and target items will be refreshed", function() {
                    spyOn(BacklogItemCollectionService, 'removeBacklogItemsFromCollection');

                    $target_list_element.data('accept', 'trackerId70|trackerId44');

                    $scope.$emit('dragularcancel',
                        $dropped_item_element[0],
                        $source_element[0]
                    );

                    expect(BacklogItemCollectionService.removeBacklogItemsFromCollection).toHaveBeenCalledWith(BacklogItemCollectionService.items[source_backlog_item.id].children.data, dropped_items);
                    expect(BacklogItemCollectionService.addOrReorderBacklogItemsInCollection).toHaveBeenCalledWith(BacklogItemCollectionService.items[target_backlog_item.id].children.data, dropped_items, null);

                    expect(BacklogItemCollectionService.items[source_backlog_item.id].updating).toBeTruthy();
                    expect(BacklogItemCollectionService.items[target_backlog_item.id].updating).toBeTruthy();

                    move_request.resolve();
                    $scope.$apply();

                    expect($source_element.children().length).toEqual(0);
                    expect(source_backlog_item.children.data).toEqual([]);
                    expect(source_backlog_item.has_children).toBeFalsy();
                    expect(source_backlog_item.children.collapsed).toBeTruthy();
                    expect(target_backlog_item.children.data).toEqual([
                        { id: dropped_item_ids[0] },
                        { id: 25 }
                    ]);
                    expect(target_backlog_item.has_children).toBeTruthy();
                    expect(DroppedService.moveFromChildrenToChildren).toHaveBeenCalledWith(
                        dropped_item_ids,
                        null,
                        source_backlog_item.id,
                        target_backlog_item.id
                    );
                    expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(source_backlog_item.id);
                    expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(target_backlog_item.id);
                });

                it("and given I can't drop into the target element, when I cancel the drop of a child (e.g. a Task) over an item (e.g. a User Story), then nothing will be changed", function() {
                    $target_list_element.data('accept', 'trackerId44');

                    $scope.$emit('dragularcancel',
                        $dropped_item_element[0],
                        $source_element[0]
                    );

                    expectNothingChanged();
                });

                it("when I cancel the drop of a child (e.g. a Task) at the same place (didn't move), then nothing will be changed", function() {
                    dragularService.shared.extra = true;

                    $scope.$emit('dragularcancel',
                        $dropped_item_element[0],
                        $source_element[0]
                    );

                    expectNothingChanged();
                });
            });

            describe("and given the target element was not a descendant of a backlog-item", function() {
                it("when I cancel the drop of a child (e.g. a Task) over an element that isn't a backlog item, then nothing will be changed", function() {
                    $target_element              = affix('div');
                    dragularService.shared.extra = $target_element;

                    $scope.$emit('dragularcancel',
                        $dropped_item_element[0],
                        $source_element[0]
                    );

                    expectNothingChanged();
                });
            });

            function expectNothingChanged() {
                    expect($source_element.children().length).toEqual(1);
                    expect(source_backlog_item.children.data).toEqual([
                        { id: dropped_item_ids[0] }
                    ]);
                    expect(source_backlog_item.has_children).toBeTruthy();
                    expect(source_backlog_item.children.collapsed).toBeFalsy();
                    expect(target_backlog_item.children.data).toEqual([
                        { id: 25 }
                    ]);
                    expect(target_backlog_item.has_children).toBeTruthy();
                    expect(target_backlog_item.children.collapsed).toBeTruthy();
                    expect(DroppedService.moveFromChildrenToChildren).not.toHaveBeenCalled();
                }
        });
    });

    describe("dragularDrop() -", function() {
        var $dropped_item_element, dropped_item_ids, dropped_items, $target_element, $source_element,
            source_model, target_model, initial_index, target_index, compared_to,
            source_backlog_item_id, move_request;

        beforeEach(function() {
            dropped_item_ids       = [78];
            dropped_items          = [{id: 78}];
            source_backlog_item_id = 20;
            $dropped_item_element  = affix('li');
            angular.element($dropped_item_element).data('item-id', dropped_item_ids[0]);
            $source_element = affix('ul.backlog-item-children');
            angular.element($source_element).data('backlog-item-id', source_backlog_item_id);
            initial_index = 0;
            target_index  = 0;
            compared_to   = {
                direction: 'before',
                item_id: 41
            };

            BacklogItemController.initDragularForBacklogItemChildren();

            move_request = $q.defer();
        });

        describe("Given an event, the dropped element, the target element, the source element, the source model, the initial index, the target model and the target index", function() {
            it("when I reorder a child (e.g. a Task) in the same item (e.g. a User Story), then the child will be reordered using DroppedService", function() {
                DroppedService.reorderBacklogItemChildren.and.returnValue(move_request.promise);
                $target_element = $source_element;
                source_model = [
                    { id: dropped_item_ids[0] },
                    { id: 41 }
                ];
                target_model = undefined;

                $scope.$emit('dragulardrop',
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index
                );

                expect(DroppedService.reorderBacklogItemChildren).toHaveBeenCalledWith(dropped_item_ids, compared_to, source_backlog_item_id);
            });

            it("when I move a child (e.g. a Task) from an item (e.g. a User Story) to another, then the child will be moved using DroppedService and both the source and target items will be refreshed", function() {
                spyOn(BacklogItemCollectionService, 'removeBacklogItemsFromCollection');

                DroppedService.moveFromChildrenToChildren.and.returnValue(move_request.promise);
                var target_backlog_item_id = 64;
                $target_element = affix('ul.backlog-item-children');
                angular.element($target_element).data('backlog-item-id', target_backlog_item_id);
                source_model = [];
                target_model = [
                    { id: dropped_item_ids[0] },
                    { id: 41 }
                ];
                BacklogItemCollectionService.items[source_backlog_item_id] = {
                    id: source_backlog_item_id,
                    updating: false,
                    children: {
                        data: []
                    }
                };
                BacklogItemCollectionService.items[target_backlog_item_id] = {
                    id: target_backlog_item_id,
                    updating: false,
                    children: {
                        data: []
                    }
                };

                $scope.$emit('dragulardrop',
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index
                );

                expect(BacklogItemCollectionService.items[source_backlog_item_id].updating).toBeTruthy();
                expect(BacklogItemCollectionService.items[target_backlog_item_id].updating).toBeTruthy();

                expect(BacklogItemCollectionService.removeBacklogItemsFromCollection).toHaveBeenCalledWith(BacklogItemCollectionService.items[source_backlog_item_id].children.data, dropped_items);
                expect(BacklogItemCollectionService.addOrReorderBacklogItemsInCollection).toHaveBeenCalledWith(BacklogItemCollectionService.items[target_backlog_item_id].children.data, dropped_items, compared_to);

                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromChildrenToChildren).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    source_backlog_item_id,
                    target_backlog_item_id
                );
                expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(source_backlog_item_id);
                expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(target_backlog_item_id);
            });
        });
    });

    describe("dragularOptionsForBacklogItemChildren() -", function() {
        describe("accepts() -", function() {
            var $element_to_drop, $target_container_element;
            beforeEach(function() {
                $element_to_drop          = affix('li');
                $target_container_element = affix('ul');
            });

            describe("Given an element to drop and a target container element", function() {
                it("and given that the element's type was in the container's accepted types, when I check if the element can be dropped, then it will return true", function() {
                    angular.element($element_to_drop).data('type', 'trackerId49');
                    angular.element($target_container_element).data('accept', 'trackerId38|trackerId49');

                    var result = BacklogItemController.dragularOptionsForBacklogItemChildren().accepts($element_to_drop, $target_container_element);

                    expect(result).toBeTruthy();
                });

                it("and given that the element's type was not in the container's accepted types, when I check if the element can be dropped, then it will return false", function() {
                    angular.element($element_to_drop).data('type', 'trackerId49');
                    angular.element($target_container_element).data('accept', 'trackerId38');

                    var result = BacklogItemController.dragularOptionsForBacklogItemChildren().accepts($element_to_drop, $target_container_element);

                    expect(result).toBeFalsy();
                });

                it("and given that the container had nodrop data, when I check if the element can be dropped, then it will return false", function() {
                    angular.element($target_container_element).data('nodrop', true);

                    var result = BacklogItemController.dragularOptionsForBacklogItemChildren().accepts($element_to_drop, $target_container_element);

                    expect(result).toBeFalsy();
                });
            });
        });

        describe("moves() -", function() {
            var $element_to_drag, $container, $handle_element;
            beforeEach(function() {
                $element_to_drag = affix('li');
                $container       = undefined;
            });

            describe("Given an element to drag and its child handle element", function() {
                it("and given that the handle has an ancestor with the 'dragular-handle-child' class and the element didn't have nodrag data, when I check if the element can be dragged, then it will return true", function() {
                    var $handle_element = $element_to_drag.affix('div.dragular-handle-child').affix('span');

                    var result = BacklogItemController.dragularOptionsForBacklogItemChildren().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeTruthy();
                });

                it("and given that the handle didn't have any ancestor with the 'dragular-handle-child' class and the element didn't have nodrag data, when I check if the element can be dragged, then it will return false", function() {
                    var $handle_element = $element_to_drag.affix('span');

                    var result = BacklogItemController.dragularOptionsForBacklogItemChildren().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeFalsy();
                });

                it("and given that the element had nodrag data, when I check if the element can be dragged, then it will return false", function() {
                    angular.element($element_to_drag).data('nodrag', true);

                    var result = BacklogItemController.dragularOptionsForBacklogItemChildren().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeFalsy();
                });
            });
        });
    });

    describe("reorderBacklogItemChildren() - ", function() {
        it("reorder backlog item's children", function() {
            var dropped_request = $q.defer(),
                backlog_item_id = 8,
                backlog_items   = [{id: 1}, {id: 2}],
                compared_to     = {item_id: 3, direction: "before"};

            BacklogItemController.backlog_item = {
                children: {
                    data: [
                        {id: 3},
                        backlog_items[0],
                        backlog_items[1]
                    ]
                }
            };

            DroppedService.reorderBacklogItemChildren.and.returnValue(dropped_request.promise);

            BacklogItemController.reorderBacklogItemChildren(backlog_item_id, backlog_items, compared_to);
            dropped_request.resolve();
            $scope.$apply();

            expect(BacklogItemCollectionService.addOrReorderBacklogItemsInCollection).toHaveBeenCalledWith(BacklogItemController.backlog_item.children.data, backlog_items, compared_to);
            expect(DroppedService.reorderBacklogItemChildren).toHaveBeenCalledWith([1, 2], compared_to, backlog_item_id);
        });
    });

    describe("moveToTop() -", function() {
        beforeEach(function() {
            spyOn(BacklogItemController, 'reorderBacklogItemChildren').and.returnValue($q.defer().promise);
        });

        it("move one item to the top of the backlog item children list", function() {
            var moved_backlog_item = { id: 69 };

            BacklogItemController.backlog_item = {
                id: 1234,
                children: {
                    data: [
                        { id: 50 },
                        { id: 61 },
                        moved_backlog_item,
                        { id: 88 },
                    ]
                }
            };

            BacklogItemController.moveToTop(moved_backlog_item);

            expect(BacklogItemSelectedService.areThereMultipleSelectedBaklogItems).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogItemController.reorderBacklogItemChildren).toHaveBeenCalledWith(1234, [moved_backlog_item], { direction: "before", item_id: 50});
        });

        it("move multiple items to the top of the backlog item children list", function() {
            var moved_backlog_item     = {id: 50};
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.and.returnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.and.returnValue(selected_backlog_items);

            BacklogItemController.backlog_item = {
                id: 1234,
                children: {
                    data: [
                        selected_backlog_items[0],
                        { id: 61 },
                        selected_backlog_items[1],
                        { id: 88 },
                    ]
                }
            };

            BacklogItemController.moveToTop(moved_backlog_item);

            expect(BacklogItemSelectedService.areThereMultipleSelectedBaklogItems).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogItemController.reorderBacklogItemChildren).toHaveBeenCalledWith(1234, selected_backlog_items, { direction: "before", item_id: 61});
        });
    });

    describe("moveToBottom() -", function() {
        var children_promise_request;
        beforeEach(function() {
            children_promise_request = $q.defer();
            BacklogItemController.children_promise = children_promise_request.promise;

            spyOn(BacklogItemController, 'reorderBacklogItemChildren').and.returnValue($q.defer().promise);
        });

        it("move one item to the bottom of the fully loaded backlog item children list", function() {
            var moved_backlog_item = { id: 69 };

            BacklogItemController.backlog_item = {
                id: 1234,
                children: {
                    data: [
                        { id: 50 },
                        { id: 61 },
                        moved_backlog_item,
                        { id: 88 },
                    ]
                }
            };

            BacklogItemController.moveToBottom(moved_backlog_item);
            children_promise_request.resolve();
            $scope.$apply();

            expect(BacklogItemSelectedService.areThereMultipleSelectedBaklogItems).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogItemController.reorderBacklogItemChildren).toHaveBeenCalledWith(1234, [moved_backlog_item], { direction: "after", item_id: 88});
        });

        it("move multiple items to the bottom of the not fully loaded backlog item children list", function() {
            var moved_backlog_item     = {id: 50};
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.and.returnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.and.returnValue(selected_backlog_items);

            BacklogItemController.backlog_item = {
                id: 1234,
                children: {
                    data: [
                        { id: 50 },
                        { id: 61 },
                        moved_backlog_item,
                        { id: 88 },
                    ]
                }
            };

            BacklogItemController.moveToBottom(moved_backlog_item);

            children_promise_request.resolve();
            $scope.$apply();

            expect(BacklogItemSelectedService.areThereMultipleSelectedBaklogItems).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogItemController.reorderBacklogItemChildren).toHaveBeenCalledWith(1234, selected_backlog_items, { direction: "after", item_id: 88});
        });
    });
});
