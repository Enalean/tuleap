import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";

import BaseMilestoneController from "./milestone-controller.js";

function createElement(tag_name, class_name) {
    const local_document = document.implementation.createHTMLDocument();
    const element = local_document.createElement(tag_name);
    if (!class_name) {
        return element;
    }
    element.classList.add(class_name);
    return element;
}

describe("MilestoneController", function () {
    let $q,
        $scope,
        $document,
        $timeout,
        dragularService,
        MilestoneController,
        BacklogService,
        DroppedService,
        MilestoneCollectionService,
        BacklogItemSelectedService;

    beforeEach(function () {
        angular.mock.module(planning_module);

        angular.mock.inject(
            function (
                _$q_,
                _$document_,
                _$timeout_,
                $rootScope,
                _dragularService_,
                $controller,
                _BacklogService_,
                _DroppedService_,
                _MilestoneCollectionService_,
                _BacklogItemSelectedService_,
            ) {
                $q = _$q_;
                $scope = $rootScope.$new();
                $scope.milestone = {
                    id: 93849,
                    content: [],
                };
                $document = _$document_;
                $timeout = _$timeout_;

                dragularService = _dragularService_;

                BacklogService = _BacklogService_;
                jest.spyOn(BacklogService, "addOrReorderBacklogItemsInBacklog").mockImplementation(
                    () => {},
                );

                DroppedService = _DroppedService_;
                jest.spyOn(DroppedService, "moveFromSubmilestoneToBacklog").mockImplementation(
                    () => {},
                );
                jest.spyOn(DroppedService, "moveFromSubmilestoneToSubmilestone").mockImplementation(
                    () => {},
                );
                jest.spyOn(DroppedService, "defineComparedToBeFirstItem");
                jest.spyOn(DroppedService, "defineComparedToBeLastItem");
                jest.spyOn(DroppedService, "reorderSubmilestone").mockImplementation(() => {});

                MilestoneCollectionService = _MilestoneCollectionService_;
                jest.spyOn(MilestoneCollectionService, "refreshMilestone").mockImplementation(
                    () => {},
                );
                jest.spyOn(
                    MilestoneCollectionService,
                    "removeBacklogItemsFromMilestoneContent",
                ).mockImplementation(() => {});
                jest.spyOn(
                    MilestoneCollectionService,
                    "addOrReorderBacklogItemsInMilestoneContent",
                ).mockImplementation(() => {});

                BacklogItemSelectedService = _BacklogItemSelectedService_;
                jest.spyOn(
                    BacklogItemSelectedService,
                    "areThereMultipleSelectedBaklogItems",
                ).mockImplementation(() => {});
                jest.spyOn(
                    BacklogItemSelectedService,
                    "getCompactedSelectedBacklogItem",
                ).mockImplementation(() => {});

                MilestoneController = $controller(BaseMilestoneController, {
                    $scope: $scope,
                    $timeout: $timeout,
                    $document: $document,
                    dragularService: dragularService,
                    BacklogService: BacklogService,
                    DroppedService: DroppedService,
                    MilestoneCollectionService: MilestoneCollectionService,
                    BacklogItemSelectedService: BacklogItemSelectedService,
                });
            },
        );
    });

    describe("toggleMilestone() -", () => {
        describe("Given a milestone object", () => {
            it("that was already loaded and collapsed, when I toggle a milestone, then it will be un-collapsed", () => {
                MilestoneController.milestone = {
                    collapsed: true,
                    alreadyLoaded: true,
                };

                MilestoneController.toggleMilestone();

                expect(MilestoneController.milestone.collapsed).toBeFalsy();
            });

            it("that was already loaded and was not collapsed, when I toggle a milestone, then it will be collapsed", () => {
                MilestoneController.milestone = {
                    collapsed: false,
                    alreadyLoaded: true,
                };

                MilestoneController.toggleMilestone();

                expect(MilestoneController.milestone.collapsed).toBeTruthy();
            });

            it("that was not already loaded, when I toggle a milestone, then its content will be loaded", () => {
                MilestoneController.milestone = {
                    content: [],
                    getContent: jest.fn(),
                };

                var get_content_request = $q.defer();
                MilestoneController.milestone.getContent.mockReturnValue(
                    get_content_request.promise,
                );
                get_content_request.resolve({
                    results: [],
                    total: 0,
                });

                MilestoneController.toggleMilestone();

                expect(MilestoneController.milestone.getContent).toHaveBeenCalled();
            });
        });
    });

    describe("isMilestoneLoadedAndEmpty() -", function () {
        it("Given a milestone that was loaded and had no children backlog items, when I check if the milestone is loaded and empty, then it will return true", function () {
            MilestoneController.milestone = {
                loadingContent: false,
                alreadyLoaded: true,
                content: [],
            };

            var result = MilestoneController.isMilestoneLoadedAndEmpty();

            expect(result).toBeTruthy();
        });

        it("Given a milestone that was loading, when I check if the milestone is loaded and empty, then it will return false", function () {
            MilestoneController.milestone = {
                loadingContent: true,
            };

            var result = MilestoneController.isMilestoneLoadedAndEmpty();

            expect(result).toBeFalsy();
        });

        it("Given a milestone that was loaded and had children backlog items, when I check if the milestone is loaded and empty, then it will return false", function () {
            MilestoneController.milestone = {
                loadingContent: false,
                alreadyLoaded: true,
                content: [{ id: 11 }],
            };

            var result = MilestoneController.isMilestoneLoadedAndEmpty();

            expect(result).toBeFalsy();
        });
    });

    describe("dragularDrop()", () => {
        let $dropped_item_element,
            dropped_item_ids,
            dropped_items,
            $target_element,
            $source_element,
            source_model,
            target_model,
            initial_index,
            target_index,
            compared_to,
            source_milestone_id,
            move_request;

        beforeEach(() => {
            dropped_item_ids = [33];
            dropped_items = [{ id: 33 }];
            source_milestone_id = 81;
            $dropped_item_element = createElement("li");
            angular.element($dropped_item_element).data("item-id", dropped_item_ids[0]);
            $source_element = createElement("ul", "submilestone");
            angular.element($source_element).data("submilestone-id", source_milestone_id);
            initial_index = 0;
            target_index = 0;
            compared_to = {
                direction: "before",
                item_id: 96,
            };

            move_request = $q.defer();

            MilestoneController.initDragularForMilestone();
        });

        describe("Given an event, the dropped element, the target element, the source element, the source model, the initial index, the target model and the target index", function () {
            it("when I reorder an item in the submilestone (e.g. a Sprint), then the item will be reordered using DroppedService", function () {
                DroppedService.reorderSubmilestone.mockReturnValue(move_request.promise);
                $target_element = $source_element;
                source_model = [{ id: dropped_item_ids[0] }, { id: 96 }];
                target_model = undefined;

                $scope.$emit(
                    "dragulardrop",
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index,
                );

                expect(DroppedService.reorderSubmilestone).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    source_milestone_id,
                );
            });

            it("when I move an item from one submilestone (e.g. a Sprint) to another submilestone, then the item will be moved using DroppedService and both the source and target submilestones' initial efforts will be updated", function () {
                DroppedService.moveFromSubmilestoneToSubmilestone.mockReturnValue(
                    move_request.promise,
                );
                var target_milestone_id = 14;
                $target_element = createElement("ul", "submilestone");
                angular.element($target_element).data("submilestone-id", target_milestone_id);
                source_model = [];
                target_model = [{ id: dropped_item_ids[0] }, { id: 96 }];

                $scope.$emit(
                    "dragulardrop",
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index,
                );
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromSubmilestoneToSubmilestone).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    source_milestone_id,
                    target_milestone_id,
                );
                expect(MilestoneCollectionService.refreshMilestone).toHaveBeenCalledWith(
                    source_milestone_id,
                );
                expect(MilestoneCollectionService.refreshMilestone).toHaveBeenCalledWith(
                    target_milestone_id,
                );
            });

            it("when I move an item from a submilestone (e.g. a Sprint) to the backlog, then the item will be moved using DroppedService and the submilestone's initial effort will be updated", function () {
                DroppedService.moveFromSubmilestoneToBacklog.mockReturnValue(move_request.promise);
                $target_element = createElement("ul", "backlog");
                source_model = [];
                target_model = [{ id: dropped_item_ids[0] }, { id: 96 }];

                $scope.$emit(
                    "dragulardrop",
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index,
                );
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromSubmilestoneToBacklog).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    source_milestone_id,
                    BacklogService.backlog,
                );
                expect(BacklogService.addOrReorderBacklogItemsInBacklog).toHaveBeenCalledWith(
                    dropped_items,
                    compared_to,
                );
                expect(MilestoneCollectionService.refreshMilestone).toHaveBeenCalledWith(
                    source_milestone_id,
                );
            });
        });

        describe("dragularOptionsForMilestone()", () => {
            describe("accepts()", () => {
                var $element_to_drop, $target_container_element;
                beforeEach(() => {
                    $element_to_drop = createElement("li");
                    $target_container_element = createElement("ul");
                });

                describe("Given an element to drop and a target container element", function () {
                    it("and given that the element's type was in the container's accepted types, when I check if the element can be dropped, then it will return true", function () {
                        angular.element($element_to_drop).data("type", "trackerId49");
                        angular
                            .element($target_container_element)
                            .data("accept", "trackerId38|trackerId49");

                        var result = MilestoneController.dragularOptionsForMilestone().accepts(
                            $element_to_drop,
                            $target_container_element,
                        );

                        expect(result).toBeTruthy();
                    });

                    it("and given that the element's type was not in the container's accepted types, when I check if the element can be dropped, then it will return false", function () {
                        angular.element($element_to_drop).data("type", "trackerId49");
                        angular.element($target_container_element).data("accept", "trackerId38");

                        var result = MilestoneController.dragularOptionsForMilestone().accepts(
                            $element_to_drop,
                            $target_container_element,
                        );

                        expect(result).toBeFalsy();
                    });

                    it("and given that the container had nodrop data, when I check if the element can be dropped, then it will return false", function () {
                        angular.element($target_container_element).data("nodrop", true);

                        var result = MilestoneController.dragularOptionsForMilestone().accepts(
                            $element_to_drop,
                            $target_container_element,
                        );

                        expect(result).toBeFalsy();
                    });
                });
            });

            describe("moves()", () => {
                var $element_to_drag, $container, $handle_element;
                beforeEach(() => {
                    $element_to_drag = createElement("li");
                    $container = undefined;
                });

                describe("Given an element to drag and its child handle element", () => {
                    it(`and given that the handle has an ancestor with the 'dragular-handle' class
                        and the element didn't have nodrag data,
                        when I check if the element can be dragged,
                        then it will return true`, () => {
                        const dragular_handle = createElement("div", "dragular-handle");
                        const $handle_element = createElement("span");
                        dragular_handle.appendChild($handle_element);
                        $element_to_drag.appendChild(dragular_handle);

                        var result = MilestoneController.dragularOptionsForMilestone().moves(
                            $element_to_drag,
                            $container,
                            $handle_element,
                        );

                        expect(result).toBeTruthy();
                    });

                    it(`and given that the handle didn't have any ancestor with the 'dragular-handle' class
                        and the element didn't have nodrag data,
                        when I check if the element can be dragged,
                        then it will return false`, () => {
                        const $handle_element = createElement("span");
                        $element_to_drag.appendChild($handle_element);

                        var result = MilestoneController.dragularOptionsForMilestone().moves(
                            $element_to_drag,
                            $container,
                            $handle_element,
                        );

                        expect(result).toBeFalsy();
                    });

                    it("and given that the element had nodrag data, when I check if the element can be dragged, then it will return false", function () {
                        angular.element($element_to_drag).data("nodrag", true);

                        var result = MilestoneController.dragularOptionsForMilestone().moves(
                            $element_to_drag,
                            $container,
                            $handle_element,
                        );

                        expect(result).toBeFalsy();
                    });
                });
            });
        });
    });

    describe("reorderMilestoneContent() -", function () {
        it("reorder the content of a milestone", function () {
            var dropped_request = $q.defer(),
                milestone_id = 12,
                backlog_items = [{ id: 1 }, { id: 2 }],
                compared_to = { item_id: 3, direction: "before" };

            DroppedService.reorderSubmilestone.mockReturnValue(dropped_request.promise);

            MilestoneController.reorderMilestoneContent(milestone_id, backlog_items, compared_to);
            dropped_request.resolve();
            $scope.$apply();

            expect(
                MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent,
            ).toHaveBeenCalledWith(milestone_id, backlog_items, compared_to);
            expect(DroppedService.reorderSubmilestone).toHaveBeenCalledWith(
                [1, 2],
                compared_to,
                milestone_id,
            );
        });
    });

    describe("moveToTop() -", function () {
        beforeEach(function () {
            jest.spyOn(MilestoneController, "reorderMilestoneContent").mockReturnValue(
                $q.defer().promise,
            );
        });

        it("move one item to the top of the milestone", function () {
            var moved_backlog_item = { id: 69 };

            MilestoneController.milestone = {
                id: 1234,
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
            };

            MilestoneController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems,
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(MilestoneController.reorderMilestoneContent).toHaveBeenCalledWith(
                1234,
                [moved_backlog_item],
                { direction: "before", item_id: 50 },
            );
        });

        it("move multiple items to the top of the milestone", function () {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.mockReturnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.mockReturnValue(
                selected_backlog_items,
            );

            MilestoneController.milestone = {
                id: 1234,
                content: [
                    selected_backlog_items[0],
                    { id: 61 },
                    selected_backlog_items[1],
                    { id: 88 },
                ],
            };

            MilestoneController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems,
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(MilestoneController.reorderMilestoneContent).toHaveBeenCalledWith(
                1234,
                selected_backlog_items,
                { direction: "before", item_id: 61 },
            );
        });
    });

    describe("moveToBottom() -", function () {
        var get_content_promise_request;
        beforeEach(function () {
            get_content_promise_request = $q.defer();
            MilestoneController.get_content_promise = get_content_promise_request.promise;

            jest.spyOn(MilestoneController, "reorderMilestoneContent").mockReturnValue(
                $q.defer().promise,
            );
        });

        it("move one item to the bottom of the fully loaded milestone", function () {
            var moved_backlog_item = { id: 69 };

            MilestoneController.milestone = {
                id: 1234,
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                pagination: {
                    limit: 50,
                    offset: 0,
                },
            };

            MilestoneController.moveToBottom(moved_backlog_item);
            get_content_promise_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems,
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(MilestoneController.reorderMilestoneContent).toHaveBeenCalledWith(
                1234,
                [moved_backlog_item],
                { direction: "after", item_id: 88 },
            );
        });

        it("move multiple items to the bottom of the not fully loaded milestone", function () {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.mockReturnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.mockReturnValue(
                selected_backlog_items,
            );

            MilestoneController.milestone = {
                id: 1234,
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                pagination: {
                    limit: 2,
                    offset: 0,
                },
            };

            MilestoneController.moveToBottom(moved_backlog_item);

            get_content_promise_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems,
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(MilestoneController.reorderMilestoneContent).toHaveBeenCalledWith(
                1234,
                selected_backlog_items,
                { direction: "after", item_id: 88 },
            );
        });
    });

    describe("hasExternalParent() -", function () {
        it("Given a milestone without original parent, then it will return false", function () {
            MilestoneController.milestone = {
                original_project_provider: null,
            };

            var result = MilestoneController.hasExternalParent();

            expect(result).toBeFalsy();
        });

        it("Given a milestone with original parent, then it will return true", function () {
            MilestoneController.milestone = {
                original_project_provider: { id: 101, description: "AAA", label: "parent label" },
            };

            var result = MilestoneController.hasExternalParent();

            expect(result).toBeTruthy();
        });
    });
});
