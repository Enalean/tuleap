describe("BacklogController - ", function() {
    var $q, $scope, BacklogController, BacklogService, DroppedService, MilestoneCollectionService;

    beforeEach(function() {
        module('backlog');

        inject(function(
            _$q_,
            $rootScope,
            $controller,
            _BacklogService_,
            _DroppedService_,
            _MilestoneCollectionService_
        ) {
            $q             = _$q_;
            $scope         = $rootScope.$new();

            BacklogService = _BacklogService_;
            spyOn(BacklogService, 'removeItemFromUnfilteredBacklog');

            DroppedService = _DroppedService_;
            spyOn(DroppedService, 'moveFromBacklogToSubmilestone');
            spyOn(DroppedService, 'reorderBacklog');

            MilestoneCollectionService = _MilestoneCollectionService_;
            spyOn(MilestoneCollectionService, 'refreshMilestone');

            BacklogController = $controller('BacklogController', {
                $scope                    : $scope,
                BacklogService            : BacklogService,
                DroppedService            : DroppedService,
                MilestoneCollectionService: MilestoneCollectionService
            });
        });
    });

    describe("displayUserCantPrioritize() -", function() {
        it("Given that the user cannot move cards in the backlog and the backlog is empty, when I check, then it will return false", function() {
            BacklogService.backlog.user_can_move_cards = false;
            BacklogService.items.content = [];

            var result = BacklogController.displayUserCantPrioritize();

            expect(result).toBeFalsy();
        });

        it("Given that the user cannot move cards in the backlog and the backlog is not empty, when I check, then it will return true", function() {
            BacklogService.backlog.user_can_move_cards = false;
            BacklogService.items.content = [
                { id: 448 }
            ];

            var result = BacklogController.displayUserCantPrioritize();

            expect(result).toBeTruthy();
        });
    });

    describe("isBacklogLoadedAndEmpty() -", function() {
        it("Given that the backlog was loaded and had no children backlog items, when I check if the backlog is loaded and empty, then it will return true", function() {
            BacklogService.items.loading      = false;
            BacklogService.items.fully_loaded = true;
            BacklogService.items.content      = [];

            var result = BacklogController.isBacklogLoadedAndEmpty();

            expect(result).toBeTruthy();
        });

        it("Given that the backlog was loading, when I check if the backlog is loaded and empty, then it will return false", function() {
            BacklogService.items.loading = true;

            var result = BacklogController.isBacklogLoadedAndEmpty();

            expect(result).toBeFalsy();
        });

        it("Given that the backlog was loaded and had children backlog items, when I check if the backlog is loaded and empty, then it will return false", function() {
            BacklogService.items.loading      = false;
            BacklogService.items.fully_loaded = true;
            BacklogService.items.content      = [
                { id: 92 }
            ];

            var result = BacklogController.isBacklogLoadedAndEmpty();

            expect(result).toBeFalsy();
        });
    });

    describe("dragularDrop() -", function() {
        var $dropped_item_element, dropped_item_id, $target_element, $source_element,
            source_model, target_model, initial_index, target_index, compared_to,
            move_request;

        beforeEach(function() {
            dropped_item_id       = 78;
            $dropped_item_element = affix('li');
            angular.element($dropped_item_element).data('item-id', dropped_item_id);
            $source_element = affix('ul.backlog');
            initial_index   = 0;
            target_index    = 0;
            compared_to     = {
                direction: 'before',
                item_id: 53
            };

            move_request = $q.defer();
        });

        describe("Given an event, the dropped element, the target element, the source element, the source model, the initial index, the target model and the target index", function() {
            it("when I reorder an item in the backlog, then the item will be reordered using DroppedService", function() {
                DroppedService.reorderBacklog.and.returnValue(move_request.promise);
                $target_element = $source_element;
                source_model = [
                    { id: dropped_item_id },
                    { id: 53 }
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

                expect(DroppedService.reorderBacklog).toHaveBeenCalledWith(dropped_item_id, compared_to, BacklogService.backlog);
            });

            it("when I move an item from the backlog to a submilestone (e.g. to a Sprint), then the item will be moved using DroppedService and the submilestone's initial effort will be updated", function() {
                DroppedService.moveFromBacklogToSubmilestone.and.returnValue(move_request.promise);
                var destination_milestone_id = 80;
                $target_element = affix('ul.submilestone');
                angular.element($target_element).data('submilestone-id', destination_milestone_id);
                source_model = [];
                target_model = [
                    { id: dropped_item_id },
                    { id: 53 }
                ];

                $scope.$emit('dragulardrop',
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index
                );
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromBacklogToSubmilestone).toHaveBeenCalledWith(dropped_item_id, compared_to, destination_milestone_id);
                expect(BacklogService.removeItemFromUnfilteredBacklog).toHaveBeenCalledWith(dropped_item_id);
                expect(MilestoneCollectionService.refreshMilestone).toHaveBeenCalledWith(destination_milestone_id);
            });
        });
    });

    describe("dragularOptions() -", function() {
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

                    var result = BacklogController.dragularOptions().accepts($element_to_drop, $target_container_element);

                    expect(result).toBeTruthy();
                });

                it("and given that the element's type was not in the container's accepted types, when I check if the element can be dropped, then it will return false", function() {
                    angular.element($element_to_drop).data('type', 'trackerId49');
                    angular.element($target_container_element).data('accept', 'trackerId38');

                    var result = BacklogController.dragularOptions().accepts($element_to_drop, $target_container_element);

                    expect(result).toBeFalsy();
                });

                it("and given that the container had nodrop data, when I check if the element can be dropped, then it will return false", function() {
                    angular.element($target_container_element).data('nodrop', true);

                    var result = BacklogController.dragularOptions().accepts($element_to_drop, $target_container_element);

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
                it("and given that the handle has an ancestor with the 'dragular-handle' class and the element didn't have nodrag data, when I check if the element can be dragged, then it will return true", function() {
                    var $handle_element = $element_to_drag.affix('div.dragular-handle').affix('span');

                    var result = BacklogController.dragularOptions().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeTruthy();
                });

                it("and given that the handle didn't have any ancestor with the 'dragular-handle' class and the element didn't have nodrag data, when I check if the element can be dragged, then it will return false", function() {
                    var $handle_element = $element_to_drag.affix('span');

                    var result = BacklogController.dragularOptions().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeFalsy();
                });

                it("and given that the element had nodrag data, when I check if the element can be dragged, then it will return false", function() {
                    angular.element($element_to_drag).data('nodrag', true);

                    var result = BacklogController.dragularOptions().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeFalsy();
                });
            });
        });
    });
});
