import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";

import BaseBacklogItemController from "./backlog-item-controller.js";

function createElement(tag_name, class_name) {
    const local_document = document.implementation.createHTMLDocument();
    const element = local_document.createElement(tag_name);
    if (!class_name) {
        return element;
    }
    element.classList.add(class_name);
    return element;
}

describe("BacklogItemController -", function () {
    var $q,
        $rootScope,
        $scope,
        $compile,
        $document,
        $element,
        BacklogItemController,
        BacklogItemService,
        BacklogItemSelectedService,
        CardFieldsService,
        DroppedService,
        BacklogItemCollectionService,
        dragularService;

    beforeEach(function () {
        angular.mock.module(planning_module);

        angular.mock.inject(
            function (
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
            ) {
                $q = _$q_;
                $rootScope = _$rootScope_;
                $scope = $rootScope.$new();

                const backlog_item = {
                    id: 352,
                    children: {
                        data: [],
                        loaded: false,
                        collapsed: false,
                    },
                };

                $scope.backlog_item = backlog_item;
                $compile = _$compile_;
                $document = _$document_;

                const current_backlog_item = createElement("div", "backlog-item");
                $element = angular.element(current_backlog_item);

                BacklogItemService = _BacklogItemService_;
                jest.spyOn(BacklogItemService, "getBacklogItemChildren").mockImplementation(
                    () => {},
                );
                jest.spyOn(BacklogItemService, "getBacklogItem").mockImplementation(() => {});
                jest.spyOn(BacklogItemService, "removeAddBacklogItemChildren").mockImplementation(
                    () => {},
                );

                DroppedService = _DroppedService_;
                jest.spyOn(DroppedService, "reorderBacklogItemChildren").mockImplementation(
                    () => {},
                );
                jest.spyOn(DroppedService, "moveFromChildrenToChildren").mockImplementation(
                    () => {},
                );
                jest.spyOn(DroppedService, "defineComparedToBeFirstItem");
                jest.spyOn(DroppedService, "defineComparedToBeLastItem");

                CardFieldsService = _CardFieldsService_;

                BacklogItemCollectionService = _BacklogItemCollectionService_;
                BacklogItemCollectionService.items[backlog_item.id] = backlog_item;

                jest.spyOn(BacklogItemCollectionService, "refreshBacklogItem").mockImplementation(
                    () => {},
                );
                jest.spyOn(
                    BacklogItemCollectionService,
                    "addOrReorderBacklogItemsInCollection",
                ).mockImplementation(() => {});

                dragularService = _dragularService_;

                BacklogItemSelectedService = _BacklogItemSelectedService_;
                jest.spyOn(
                    BacklogItemSelectedService,
                    "areThereMultipleSelectedBaklogItems",
                ).mockImplementation(() => {});
                jest.spyOn(
                    BacklogItemSelectedService,
                    "getCompactedSelectedBacklogItem",
                ).mockImplementation(() => {});

                BacklogItemController = $controller(BaseBacklogItemController, {
                    $scope: $scope,
                    $element: $element,
                    $document: $document,
                    BacklogItemService: BacklogItemService,
                    dragularService: dragularService,
                    DroppedService: DroppedService,
                    CardFieldsService: CardFieldsService,
                    BacklogItemCollectionService: BacklogItemCollectionService,
                    BacklogItemSelectedService: BacklogItemSelectedService,
                });
            },
        );
    });

    describe("toggleChildrenDisplayed() -", function () {
        var get_backlog_item_children_request;

        beforeEach(function () {
            get_backlog_item_children_request = $q.defer();
            BacklogItemService.getBacklogItemChildren.mockReturnValue(
                get_backlog_item_children_request.promise,
            );
        });

        describe("Given a backlog item", function () {
            it("with children that were not already loaded, when I show its children, then the item's children will be loaded and un-collapsed", function () {
                BacklogItemController.backlog_item = {
                    id: 352,
                    has_children: true,
                    children: {
                        collapsed: true,
                        data: [],
                        loaded: false,
                    },
                };

                BacklogItemController.toggleChildrenDisplayed();
                expect(BacklogItemController.backlog_item.loading).toBeTruthy();
                get_backlog_item_children_request.resolve({
                    results: [{ id: 151 }, { id: 857 }],
                    total: 2,
                });
                $scope.$apply();

                expect(BacklogItemService.getBacklogItemChildren).toHaveBeenCalledWith(352, 50, 0);
                expect(BacklogItemController.backlog_item.loading).toBeFalsy();
                expect(BacklogItemController.backlog_item.children.collapsed).toBeFalsy();
                expect(BacklogItemController.backlog_item.children.loaded).toBeTruthy();
                expect(BacklogItemController.backlog_item.children.data).toEqual([
                    { id: 151 },
                    { id: 857 },
                ]);
            });

            it("with no children, when I show its children, then BacklogItemService won't be called", function () {
                BacklogItemController.backlog_item = {
                    has_children: false,
                    children: {
                        collapsed: true,
                    },
                };

                BacklogItemController.toggleChildrenDisplayed();

                expect(BacklogItemService.getBacklogItemChildren).not.toHaveBeenCalled();
                expect(BacklogItemController.backlog_item.loading).toBeFalsy();
                expect(BacklogItemController.backlog_item.children.collapsed).toBeTruthy();
            });

            it("with children that were already loaded and collapsed, when I show its children, then BacklogItemService won't be called and the item's children will be un-collapsed", function () {
                BacklogItemController.backlog_item = {
                    has_children: true,
                    children: {
                        collapsed: true,
                        loaded: true,
                    },
                };

                BacklogItemController.toggleChildrenDisplayed();

                expect(BacklogItemService.getBacklogItemChildren).not.toHaveBeenCalled();
                expect(BacklogItemController.backlog_item.children.collapsed).toBeFalsy();
            });
        });
    });

    describe("dragularEnter() -", function () {
        var $dropped_item_element,
            dropped_item_ids,
            $source_element,
            $target_list_element,
            source_backlog_item_id,
            target_backlog_item_id;

        beforeEach(function () {
            dropped_item_ids = [18];
            source_backlog_item_id = 57;
            target_backlog_item_id = 64;
            $dropped_item_element = createElement("li");
            angular.element($dropped_item_element).data("item-id", dropped_item_ids);
            angular.element($dropped_item_element).data("type", "trackerId24");
            dragularService.shared.item = $dropped_item_element;
            $source_element = createElement("ul", "backlog-item-children-list");
            angular.element($source_element).data("backlog-item-id", source_backlog_item_id);
            dragularService.shared.source = $source_element;
            jest.spyOn($element, "addClass").mockImplementation(() => {});

            $target_list_element = createElement("ul", "backlog-item-children-list");
            $element.append($target_list_element);
            $target_list_element = angular.element($target_list_element);
            $target_list_element.data("backlog-item-id", target_backlog_item_id);

            BacklogItemController.initDragularForBacklogItemChildren();
        });

        describe("Given I was dragging a child (e.g. a Task) and given a backlog item (e.g. a User Story)", function () {
            it("and given I can drop the child on it, when I drag it over the backlog item, then the 'appending-child' css class will be added to the current $element", function () {
                $target_list_element.data("accept", "trackerId24|trackerId80");

                $element.trigger("dragularenter");

                expect($element.addClass).toHaveBeenCalledWith("appending-child");
            });

            it("and given I can't drop the child on it, when I drag it over the backlog item, then the 'appending-child' css class won't be added to the current $element", function () {
                $target_list_element.data("accept", "trackerId80");

                $element.trigger("dragularenter");

                expect($element.addClass).not.toHaveBeenCalled();
            });

            it("when I drag the child over its current parent (target == source), then the 'appending-child' css class wont't be added to the current $element", function () {
                $source_element = $element;
                dragularService.shared.source = $source_element;
                $target_list_element.data("accept", "");

                $element.trigger("dragularenter");

                expect($element.addClass).not.toHaveBeenCalled();
            });
        });
    });

    describe("dragularLeave() -", () => {
        it(`Given I was dragging something,
            when I leave a backlog item,
            then the 'appending-child' css class will be removed from the current $element`, () => {
            BacklogItemController.initDragularForBacklogItemChildren();
            dragularService.shared.extra = createElement("div");

            jest.spyOn($element, "removeClass").mockImplementation(() => {});

            $element.trigger("dragularleave");

            expect($element.removeClass).toHaveBeenCalledWith("appending-child");
        });
    });

    describe("dragularRelease() -", function () {
        it("Given I was dragging something, when I release the item I was dragging, then the 'appending-child' css class will be removed from the current $element", function () {
            BacklogItemController.initDragularForBacklogItemChildren();

            jest.spyOn($element, "removeClass").mockImplementation(() => {});

            $element.trigger("dragularrelease");

            expect($element.removeClass).toHaveBeenCalledWith("appending-child");
        });
    });

    describe("dragularCancel()", () => {
        describe(`Given an event,
            the dropped element,
            the source element,
            the target element
            and the initial index`, () => {
            var $dropped_item_element,
                dropped_item_ids,
                dropped_items,
                $target_element,
                $source_element,
                $backlog_item_element,
                $target_list_element,
                source_backlog_item,
                target_backlog_item,
                initial_index,
                move_request;

            beforeEach(() => {
                dropped_item_ids = [60];
                dropped_items = [{ id: 60 }];
                $dropped_item_element = angular.element(createElement("li"));
                angular.element($dropped_item_element).data("item-id", dropped_item_ids[0]);
                angular.element($dropped_item_element).data("type", "trackerId70");
                source_backlog_item = {
                    id: 87,
                    updating: false,
                    has_children: true,
                    children: {
                        collapsed: false,
                        data: [{ id: dropped_item_ids[0] }],
                    },
                };
                $source_element = angular.element(
                    createElement("ul", "backlog-item-children-list"),
                );
                angular.element($source_element).data("backlog-item-id", source_backlog_item.id);
                $source_element.append($dropped_item_element);
                BacklogItemController.backlog_item = source_backlog_item;
                target_backlog_item = {
                    id: 51,
                    updating: false,
                    has_children: true,
                    children: {
                        collapsed: true,
                        data: [{ id: 25 }],
                    },
                };
                initial_index = 0;
                dragularService.shared.initialIndex = initial_index;
                BacklogItemCollectionService.items[source_backlog_item.id] = source_backlog_item;
                BacklogItemCollectionService.items[target_backlog_item.id] = target_backlog_item;

                BacklogItemController.initDragularForBacklogItemChildren();

                move_request = $q.defer();
                DroppedService.moveFromChildrenToChildren.mockReturnValue(move_request.promise);
            });

            describe(`and given the target element was a descendant of a backlog-item element
                that had a list of children`, () => {
                beforeEach(() => {
                    $backlog_item_element = createElement("div", "backlog-item");
                    $target_list_element = createElement("ul", "backlog-item-children-list");
                    $backlog_item_element.appendChild($target_list_element);
                    $target_list_element = angular.element($target_list_element);
                    $target_list_element.data("backlog-item-id", target_backlog_item.id);
                    $target_element = createElement("div");
                    $backlog_item_element.appendChild($target_element);
                    const target_scope = $rootScope.$new();
                    target_scope.backlog_item = target_backlog_item;
                    $compile($target_list_element)(target_scope);
                    dragularService.shared.extra = $target_element;
                });

                it(`and given I can drop into the target element,
                    when I cancel the drop of a child (e.g. a Task) over an item (e.g. a User Story),
                    then the dropped element will be removed from the source element,
                    the child will be removed from the source backlog item's model
                    and prepended to the target backlog item's model,
                    the target backlog item will be marked as having children,
                    the child will be moved using DroppedService
                    and both the source and target items will be refreshed`, () => {
                    jest.spyOn(
                        BacklogItemCollectionService,
                        "removeBacklogItemsFromCollection",
                    ).mockImplementation(() => {});

                    $target_list_element.data("accept", "trackerId70|trackerId44");

                    $scope.$emit("dragularcancel", $dropped_item_element[0], $source_element[0]);

                    expect(
                        BacklogItemCollectionService.removeBacklogItemsFromCollection,
                    ).toHaveBeenCalledWith(
                        BacklogItemCollectionService.items[source_backlog_item.id].children.data,
                        dropped_items,
                    );
                    expect(
                        BacklogItemCollectionService.addOrReorderBacklogItemsInCollection,
                    ).toHaveBeenCalledWith(
                        BacklogItemCollectionService.items[target_backlog_item.id].children.data,
                        dropped_items,
                        null,
                    );

                    expect(
                        BacklogItemCollectionService.items[source_backlog_item.id].updating,
                    ).toBeTruthy();
                    expect(
                        BacklogItemCollectionService.items[target_backlog_item.id].updating,
                    ).toBeTruthy();

                    move_request.resolve();
                    $scope.$apply();

                    expect($source_element.children()).toHaveLength(0);
                    expect(source_backlog_item.children.data).toEqual([]);
                    expect(source_backlog_item.has_children).toBeFalsy();
                    expect(source_backlog_item.children.collapsed).toBeTruthy();
                    expect(target_backlog_item.children.data).toEqual([
                        { id: dropped_item_ids[0] },
                        { id: 25 },
                    ]);
                    expect(target_backlog_item.has_children).toBeTruthy();
                    expect(DroppedService.moveFromChildrenToChildren).toHaveBeenCalledWith(
                        dropped_item_ids,
                        null,
                        source_backlog_item.id,
                        target_backlog_item.id,
                    );
                    expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(
                        source_backlog_item.id,
                    );
                    expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(
                        target_backlog_item.id,
                    );
                });

                it("and given I can't drop into the target element, when I cancel the drop of a child (e.g. a Task) over an item (e.g. a User Story), then nothing will be changed", function () {
                    $target_list_element.data("accept", "trackerId44");

                    $scope.$emit("dragularcancel", $dropped_item_element[0], $source_element[0]);

                    expectNothingChanged();
                });

                it("when I cancel the drop of a child (e.g. a Task) at the same place (didn't move), then nothing will be changed", function () {
                    dragularService.shared.extra = true;

                    $scope.$emit("dragularcancel", $dropped_item_element[0], $source_element[0]);

                    expectNothingChanged();
                });
            });

            describe("and given the target element was not a descendant of a backlog-item", function () {
                it("when I cancel the drop of a child (e.g. a Task) over an element that isn't a backlog item, then nothing will be changed", function () {
                    $target_element = createElement("div");
                    dragularService.shared.extra = $target_element;

                    $scope.$emit("dragularcancel", $dropped_item_element[0], $source_element[0]);

                    expectNothingChanged();
                });
            });

            function expectNothingChanged() {
                expect($source_element.children()).toHaveLength(1);
                expect(source_backlog_item.children.data).toEqual([{ id: dropped_item_ids[0] }]);
                expect(source_backlog_item.has_children).toBeTruthy();
                expect(source_backlog_item.children.collapsed).toBeFalsy();
                expect(target_backlog_item.children.data).toEqual([{ id: 25 }]);
                expect(target_backlog_item.has_children).toBeTruthy();
                expect(target_backlog_item.children.collapsed).toBeTruthy();
                expect(DroppedService.moveFromChildrenToChildren).not.toHaveBeenCalled();
            }
        });
    });

    describe("dragularDrop()", () => {
        var $dropped_item_element,
            dropped_item_ids,
            dropped_items,
            $target_element,
            $source_element,
            source_model,
            target_model,
            initial_index,
            target_index,
            compared_to,
            source_backlog_item_id,
            move_request;

        beforeEach(() => {
            dropped_item_ids = [78];
            dropped_items = [{ id: 78 }];
            source_backlog_item_id = 20;
            $dropped_item_element = createElement("li");
            angular.element($dropped_item_element).data("item-id", dropped_item_ids[0]);
            $source_element = createElement("ul", "backlog-item-children-list");
            angular.element($source_element).data("backlog-item-id", source_backlog_item_id);
            initial_index = 0;
            target_index = 0;
            compared_to = {
                direction: "before",
                item_id: 41,
            };

            BacklogItemController.initDragularForBacklogItemChildren();

            move_request = $q.defer();
        });

        describe("Given an event, the dropped element, the target element, the source element, the source model, the initial index, the target model and the target index", function () {
            it("when I reorder a child (e.g. a Task) in the same item (e.g. a User Story), then the child will be reordered using DroppedService", function () {
                DroppedService.reorderBacklogItemChildren.mockReturnValue(move_request.promise);
                $target_element = $source_element;
                source_model = [{ id: dropped_item_ids[0] }, { id: 41 }];
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

                expect(DroppedService.reorderBacklogItemChildren).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    source_backlog_item_id,
                );
            });

            it(`when I move a child (e.g. a Task) from an item (e.g. a User Story) to another,
                then the child will be moved using DroppedService
                and both the source and target items will be refreshed`, () => {
                jest.spyOn(
                    BacklogItemCollectionService,
                    "removeBacklogItemsFromCollection",
                ).mockImplementation(() => {});

                DroppedService.moveFromChildrenToChildren.mockReturnValue(move_request.promise);
                var target_backlog_item_id = 64;
                $target_element = createElement("ul", "backlog-item-children-list");
                angular.element($target_element).data("backlog-item-id", target_backlog_item_id);
                source_model = [];
                target_model = [{ id: dropped_item_ids[0] }, { id: 41 }];
                BacklogItemCollectionService.items[source_backlog_item_id] = {
                    id: source_backlog_item_id,
                    updating: false,
                    children: {
                        data: [],
                    },
                };
                BacklogItemCollectionService.items[target_backlog_item_id] = {
                    id: target_backlog_item_id,
                    updating: false,
                    children: {
                        data: [],
                    },
                };

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

                expect(
                    BacklogItemCollectionService.items[source_backlog_item_id].updating,
                ).toBeTruthy();
                expect(
                    BacklogItemCollectionService.items[target_backlog_item_id].updating,
                ).toBeTruthy();

                expect(
                    BacklogItemCollectionService.removeBacklogItemsFromCollection,
                ).toHaveBeenCalledWith(
                    BacklogItemCollectionService.items[source_backlog_item_id].children.data,
                    dropped_items,
                );
                expect(
                    BacklogItemCollectionService.addOrReorderBacklogItemsInCollection,
                ).toHaveBeenCalledWith(
                    BacklogItemCollectionService.items[target_backlog_item_id].children.data,
                    dropped_items,
                    compared_to,
                );

                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromChildrenToChildren).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    source_backlog_item_id,
                    target_backlog_item_id,
                );
                expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(
                    source_backlog_item_id,
                );
                expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(
                    target_backlog_item_id,
                );
            });
        });
    });

    describe("dragularOptionsForBacklogItemChildren()", () => {
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

                    var result =
                        BacklogItemController.dragularOptionsForBacklogItemChildren().accepts(
                            $element_to_drop,
                            $target_container_element,
                        );

                    expect(result).toBeTruthy();
                });

                it("and given that the element's type was not in the container's accepted types, when I check if the element can be dropped, then it will return false", function () {
                    angular.element($element_to_drop).data("type", "trackerId49");
                    angular.element($target_container_element).data("accept", "trackerId38");

                    var result =
                        BacklogItemController.dragularOptionsForBacklogItemChildren().accepts(
                            $element_to_drop,
                            $target_container_element,
                        );

                    expect(result).toBeFalsy();
                });

                it("and given that the container had nodrop data, when I check if the element can be dropped, then it will return false", function () {
                    angular.element($target_container_element).data("nodrop", true);

                    var result =
                        BacklogItemController.dragularOptionsForBacklogItemChildren().accepts(
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
                it(`and given that the handle has an ancestor with the 'dragular-handle-child' class
                    and the element didn't have nodrag data,
                    when I check if the element can be dragged,
                    then it will return true`, () => {
                    const $handle_element = angular.element(createElement("span"));
                    const handle_child = createElement("div", "dragular-handle-child");
                    angular.element(handle_child).append($handle_element);
                    $element_to_drag.appendChild(handle_child);

                    var result =
                        BacklogItemController.dragularOptionsForBacklogItemChildren().moves(
                            $element_to_drag,
                            $container,
                            $handle_element,
                        );

                    expect(result).toBeTruthy();
                });

                it(`and given that the handle didn't have any ancestor with the 'dragular-handle-child' class
                    and the element didn't have nodrag data,
                    when I check if the element can be dragged,
                    then it will return false`, () => {
                    const $handle_element = createElement("span");
                    $element_to_drag.appendChild($handle_element);

                    var result =
                        BacklogItemController.dragularOptionsForBacklogItemChildren().moves(
                            $element_to_drag,
                            $container,
                            $handle_element,
                        );

                    expect(result).toBeFalsy();
                });

                it("and given that the element had nodrag data, when I check if the element can be dragged, then it will return false", function () {
                    angular.element($element_to_drag).data("nodrag", true);

                    var result =
                        BacklogItemController.dragularOptionsForBacklogItemChildren().moves(
                            $element_to_drag,
                            $container,
                            $handle_element,
                        );

                    expect(result).toBeFalsy();
                });
            });
        });
    });

    describe("reorderBacklogItemChildren() -", function () {
        it("reorder backlog item's children", function () {
            var dropped_request = $q.defer(),
                backlog_item_id = 8,
                backlog_items = [{ id: 1 }, { id: 2 }],
                compared_to = { item_id: 3, direction: "before" };

            BacklogItemController.backlog_item = {
                children: {
                    data: [{ id: 3 }, backlog_items[0], backlog_items[1]],
                },
            };

            DroppedService.reorderBacklogItemChildren.mockReturnValue(dropped_request.promise);

            BacklogItemController.reorderBacklogItemChildren(
                backlog_item_id,
                backlog_items,
                compared_to,
            );
            dropped_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemCollectionService.addOrReorderBacklogItemsInCollection,
            ).toHaveBeenCalledWith(
                BacklogItemController.backlog_item.children.data,
                backlog_items,
                compared_to,
            );
            expect(DroppedService.reorderBacklogItemChildren).toHaveBeenCalledWith(
                [1, 2],
                compared_to,
                backlog_item_id,
            );
        });
    });

    describe("moveToTop() -", function () {
        beforeEach(function () {
            jest.spyOn(BacklogItemController, "reorderBacklogItemChildren").mockReturnValue(
                $q.defer().promise,
            );
        });

        it("move one item to the top of the backlog item children list", function () {
            var moved_backlog_item = { id: 69 };

            BacklogItemController.backlog_item = {
                id: 1234,
                children: {
                    data: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                },
            };

            BacklogItemController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems,
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogItemController.reorderBacklogItemChildren).toHaveBeenCalledWith(
                1234,
                [moved_backlog_item],
                { direction: "before", item_id: 50 },
            );
        });

        it("move multiple items to the top of the backlog item children list", function () {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.mockReturnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.mockReturnValue(
                selected_backlog_items,
            );

            BacklogItemController.backlog_item = {
                id: 1234,
                children: {
                    data: [
                        selected_backlog_items[0],
                        { id: 61 },
                        selected_backlog_items[1],
                        { id: 88 },
                    ],
                },
            };

            BacklogItemController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems,
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogItemController.reorderBacklogItemChildren).toHaveBeenCalledWith(
                1234,
                selected_backlog_items,
                { direction: "before", item_id: 61 },
            );
        });
    });

    describe("moveToBottom() -", function () {
        var children_promise_request;
        beforeEach(function () {
            children_promise_request = $q.defer();
            BacklogItemController.children_promise = children_promise_request.promise;

            jest.spyOn(BacklogItemController, "reorderBacklogItemChildren").mockReturnValue(
                $q.defer().promise,
            );
        });

        it("move one item to the bottom of the fully loaded backlog item children list", function () {
            var moved_backlog_item = { id: 69 };

            BacklogItemController.backlog_item = {
                id: 1234,
                children: {
                    data: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                },
            };

            BacklogItemController.moveToBottom(moved_backlog_item);
            children_promise_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems,
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogItemController.reorderBacklogItemChildren).toHaveBeenCalledWith(
                1234,
                [moved_backlog_item],
                { direction: "after", item_id: 88 },
            );
        });

        it("move multiple items to the bottom of the not fully loaded backlog item children list", function () {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.mockReturnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.mockReturnValue(
                selected_backlog_items,
            );

            BacklogItemController.backlog_item = {
                id: 1234,
                children: {
                    data: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                },
            };

            BacklogItemController.moveToBottom(moved_backlog_item);

            children_promise_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems,
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogItemController.reorderBacklogItemChildren).toHaveBeenCalledWith(
                1234,
                selected_backlog_items,
                { direction: "after", item_id: 88 },
            );
        });
    });
});
