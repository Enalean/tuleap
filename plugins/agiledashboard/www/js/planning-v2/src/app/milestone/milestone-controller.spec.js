describe("MilestoneController -", function() {
    var $q, $scope, MilestoneController, BacklogService, DroppedService, MilestoneCollectionService;

    beforeEach(function() {
        module('milestone');

        inject(function(
            _$q_,
            $rootScope,
            $controller,
            _BacklogService_,
            _DroppedService_,
            _MilestoneCollectionService_
        ) {
            $q                         = _$q_;
            $scope                     = $rootScope.$new();

            BacklogService = _BacklogService_;
            spyOn(BacklogService, "insertItemInUnfilteredBacklog");

            DroppedService = _DroppedService_;
            spyOn(DroppedService, "moveFromSubmilestoneToBacklog");
            spyOn(DroppedService, "moveFromSubmilestoneToSubmilestone");
            spyOn(DroppedService, "reorderSubmilestone");

            MilestoneCollectionService = _MilestoneCollectionService_;
            spyOn(MilestoneCollectionService, "refreshMilestone");

            MilestoneController = $controller('MilestoneController', {
                $scope                    : $scope,
                BacklogService            : BacklogService,
                DroppedService            : DroppedService,
                MilestoneCollectionService: MilestoneCollectionService
            });
        });
    });

    describe("toggleMilestone() -", function() {
        var event, milestone;
        describe("Given an event with a target that was not a create-item-link and a milestone object", function() {
            beforeEach(function() {
                event = {
                    target: {
                        classList: {
                            contains: function() {
                                return false;
                            }
                        }
                    }
                };
            });

            it("that was already loaded and collapsed, when I toggle a milestone, then it will be un-collapsed", function() {
                milestone = {
                    collapsed: true,
                    alreadyLoaded: true
                };

                MilestoneController.toggleMilestone(event, milestone);

                expect(milestone.collapsed).toBeFalsy();
            });

            it("that was already loaded and was not collapsed, when I toggle a milestone, then it will be collapsed", function() {
                milestone = {
                    collapsed: false,
                    alreadyLoaded: true
                };

                MilestoneController.toggleMilestone(event, milestone);

                expect(milestone.collapsed).toBeTruthy();
            });

            it("that was not already loaded, when I toggle a milestone, then its content will be loaded", function() {
                milestone = {
                    content: [],
                    getContent: jasmine.createSpy("getContent")
                };

                MilestoneController.toggleMilestone(event, milestone);

                expect(milestone.getContent).toHaveBeenCalled();
            });
        });

        it("Given an event with a create-item-link target and a collapsed milestone, when I toggle a milestone, then it will stay collapsed", function() {
            event = {
                target: {
                    parentNode: {
                        getElementsByClassName: function() {
                            return [
                                {
                                    fakeElement: ''
                                }
                            ];
                        }
                    }
                }
            };

            milestone = {
                collapsed: true,
                alreadyLoaded: true
            };

            MilestoneController.toggleMilestone(event, milestone);

            expect(milestone.collapsed).toBeTruthy();
        });
    });

    describe("isMilestoneLoadedAndEmpty() -", function() {
        it("Given a milestone that was loaded and had no children backlog items, when I check if the milestone is loaded and empty, then it will return true", function() {
            var milestone = {
                loadingContent: false,
                alreadyLoaded: true,
                content: []
            };

            var result = MilestoneController.isMilestoneLoadedAndEmpty(milestone);

            expect(result).toBeTruthy();
        });

        it("Given a milestone that was loading, when I check if the milestone is loaded and empty, then it will return false", function() {
            var milestone = {
                loadingContent: true
            };

            var result = MilestoneController.isMilestoneLoadedAndEmpty(milestone);

            expect(result).toBeFalsy();
        });

        it("Given a milestone that was loaded and had children backlog items, when I check if the milestone is loaded and empty, then it will return false", function() {
            var milestone = {
                loadingContent: false,
                alreadyLoaded: true,
                content: [
                    { id: 11 }
                ]
            };

            var result = MilestoneController.isMilestoneLoadedAndEmpty(milestone);

            expect(result).toBeFalsy();
        });
    });

    describe("dragularDrop() -", function() {
        var $dropped_item_element, dropped_item_id, $target_element, $source_element,
            source_model, target_model, initial_index, target_index, compared_to,
            source_milestone_id, move_request;

        beforeEach(function() {
            dropped_item_id       = 33;
            source_milestone_id   = 81;
            $dropped_item_element = affix('li');
            angular.element($dropped_item_element).data('item-id', dropped_item_id);
            $source_element = affix('ul.submilestone');
            angular.element($source_element).data('submilestone-id', source_milestone_id);
            initial_index = 0;
            target_index  = 0;
            compared_to   = {
                direction: 'before',
                item_id: 96
            };

            move_request = $q.defer();
        });

        describe("Given an event, the dropped element, the target element, the source element, the source model, the initial index, the target model and the target index", function() {
            it("when I reorder an item in the submilestone (e.g. a Sprint), then the item will be reordered using DroppedService", function() {
                DroppedService.reorderSubmilestone.and.returnValue(move_request.promise);
                $target_element = $source_element;
                source_model = [
                    { id: dropped_item_id },
                    { id: 96 }
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

                expect(DroppedService.reorderSubmilestone).toHaveBeenCalledWith(dropped_item_id, compared_to, source_milestone_id);
            });

            it("when I move an item from one submilestone (e.g. a Sprint) to another submilestone, then the item will be moved using DroppedService and both the source and target submilestones' initial efforts will be updated", function() {
                DroppedService.moveFromSubmilestoneToSubmilestone.and.returnValue(move_request.promise);
                var target_milestone_id = 14;
                $target_element = affix('ul.submilestone');
                angular.element($target_element).data('submilestone-id', target_milestone_id);
                source_model = [];
                target_model = [
                    { id: dropped_item_id },
                    { id: 96 }
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

                expect(DroppedService.moveFromSubmilestoneToSubmilestone).toHaveBeenCalledWith(
                    dropped_item_id,
                    compared_to,
                    source_milestone_id,
                    target_milestone_id
                );
                expect(MilestoneCollectionService.refreshMilestone).toHaveBeenCalledWith(source_milestone_id);
                expect(MilestoneCollectionService.refreshMilestone).toHaveBeenCalledWith(target_milestone_id);
            });

            it("when I move an item from a submilestone (e.g. a Sprint) to the backlog, then the item will be moved using DroppedService and the submilestone's initial effort will be updated", function() {
                DroppedService.moveFromSubmilestoneToBacklog.and.returnValue(move_request.promise);
                $target_element = affix('ul.backlog');
                source_model = [];
                target_model = [
                    { id: dropped_item_id },
                    { id: 96 }
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

                expect(DroppedService.moveFromSubmilestoneToBacklog).toHaveBeenCalledWith(
                    dropped_item_id,
                    compared_to,
                    source_milestone_id,
                    BacklogService.backlog
                );
                expect(BacklogService.insertItemInUnfilteredBacklog).toHaveBeenCalledWith(
                    { id: dropped_item_id },
                    target_index
                );
                expect(MilestoneCollectionService.refreshMilestone).toHaveBeenCalledWith(source_milestone_id);
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

                        var result = MilestoneController.dragularOptions().accepts($element_to_drop, $target_container_element);

                        expect(result).toBeTruthy();
                    });

                    it("and given that the element's type was not in the container's accepted types, when I check if the element can be dropped, then it will return false", function() {
                        angular.element($element_to_drop).data('type', 'trackerId49');
                        angular.element($target_container_element).data('accept', 'trackerId38');

                        var result = MilestoneController.dragularOptions().accepts($element_to_drop, $target_container_element);

                        expect(result).toBeFalsy();
                    });

                    it("and given that the container had nodrop data, when I check if the element can be dropped, then it will return false", function() {
                        angular.element($target_container_element).data('nodrop', true);

                        var result = MilestoneController.dragularOptions().accepts($element_to_drop, $target_container_element);

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

                        var result = MilestoneController.dragularOptions().moves(
                            $element_to_drag,
                            $container,
                            $handle_element
                        );

                        expect(result).toBeTruthy();
                    });

                    it("and given that the handle didn't have any ancestor with the 'dragular-handle' class and the element didn't have nodrag data, when I check if the element can be dragged, then it will return false", function() {
                        var $handle_element = $element_to_drag.affix('span');

                        var result = MilestoneController.dragularOptions().moves(
                            $element_to_drag,
                            $container,
                            $handle_element
                        );

                        expect(result).toBeFalsy();
                    });

                    it("and given that the element had nodrag data, when I check if the element can be dragged, then it will return false", function() {
                        angular.element($element_to_drag).data('nodrag', true);

                        var result = MilestoneController.dragularOptions().moves(
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
});
