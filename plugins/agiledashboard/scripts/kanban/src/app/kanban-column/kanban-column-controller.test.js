import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";

function createElement(tag_name, no_drag) {
    const local_document = document.implementation.createHTMLDocument();
    const element = local_document.createElement(tag_name);
    if (!no_drag) {
        return element;
    }
    element.setAttribute("data-nodrag", true);
    return element;
}

describe("KanbanColumnController -", function () {
    var $rootScope,
        $scope,
        $q,
        KanbanColumnController,
        SharedPropertiesService,
        KanbanColumnService,
        ColumnCollectionService,
        DroppedService;

    beforeEach(function () {
        angular.mock.module(kanban_module);

        var $controller, $element;

        angular.mock.inject(function (
            _$controller_,
            _$rootScope_,
            _$q_,
            _SharedPropertiesService_,
            _KanbanColumnService_,
            _ColumnCollectionService_,
            _DroppedService_
        ) {
            $controller = _$controller_;
            $rootScope = _$rootScope_;
            $q = _$q_;
            SharedPropertiesService = _SharedPropertiesService_;
            KanbanColumnService = _KanbanColumnService_;
            ColumnCollectionService = _ColumnCollectionService_;
            DroppedService = _DroppedService_;
        });

        jest.spyOn(KanbanColumnService, "moveItem").mockImplementation(() => {});
        jest.spyOn(DroppedService, "getComparedTo").mockImplementation(() => {});
        jest.spyOn(DroppedService, "reorderColumn").mockImplementation(() => {});
        jest.spyOn(DroppedService, "moveToColumn").mockImplementation(() => {});
        jest.spyOn(ColumnCollectionService, "getColumn").mockImplementation(() => {});
        jest.spyOn(SharedPropertiesService, "getKanban").mockImplementation(() => {});

        $scope = $rootScope.$new();
        $element = angular.element(createElement("div", false));

        KanbanColumnController = $controller("KanbanColumnController", {
            $scope: $scope,
            $element: $element,
            DroppedService: DroppedService,
            KanbanColumnService: KanbanColumnService,
            ColumnCollectionService: ColumnCollectionService,
            SharedPropertiesService: SharedPropertiesService,
        });

        KanbanColumnController.column = {
            filtered_content: [],
        };
    });

    describe("initDragular() -", function () {
        it("when dragular is initialized, then the drake will be published on the controller", function () {
            delete KanbanColumnController.drake;
            var drake = {
                cancel: function () {},
            };

            KanbanColumnController.dragularOptions().onInit(drake);

            expect(KanbanColumnController.drake).toBe(drake);
        });
    });

    describe("dragularDrop() -", function () {
        var current_kanban,
            $dropped_item_element,
            dropped_item,
            $target_element,
            $source_element,
            source_model,
            target_model,
            initial_index,
            target_index,
            source_column,
            target_column,
            compared_to;

        beforeEach(function () {
            current_kanban = {
                id: 8,
            };
            SharedPropertiesService.getKanban.mockReturnValue(current_kanban);

            $target_element = createElement("ul", false);

            source_column = {
                id: 69,
                filtered_content: [],
            };
            KanbanColumnController.column = source_column;
            KanbanColumnController.dragularOptions().onInit();
            jest.spyOn($rootScope, "$broadcast");
        });

        it("When I reorder an item in the same column, then the item will be reordered using DroppedService", function () {
            dropped_item = { id: 968 };
            source_model = [dropped_item, { id: 482 }];
            target_model = null;
            target_index = 0;
            compared_to = {
                direction: "before",
                item_id: 482,
            };
            angular.element($target_element).data("column-id", source_column.id);

            DroppedService.getComparedTo.mockReturnValue(compared_to);
            DroppedService.reorderColumn.mockReturnValue($q.when());
            ColumnCollectionService.getColumn.mockReturnValue(source_column);

            $scope.$emit(
                "dragulardrop",
                $dropped_item_element,
                $target_element,
                $source_element,
                source_model,
                initial_index,
                target_model,
                target_index
            );

            $scope.$apply();

            expect(DroppedService.reorderColumn).toHaveBeenCalledWith(
                current_kanban.id,
                source_column.id,
                dropped_item.id,
                compared_to
            );
            expect(KanbanColumnService.moveItem).toHaveBeenCalledWith(
                dropped_item,
                source_column,
                source_column,
                compared_to
            );

            expect($rootScope.$broadcast).toHaveBeenCalledWith("rebuild:kustom-scroll");
        });

        it("When I move an item to the archive, then the item will be move using DroppedService", function () {
            dropped_item = { id: 655 };
            source_model = [{ id: 338 }];
            target_model = [{ id: 462 }, dropped_item];
            target_index = 1;
            compared_to = {
                direction: "after",
                item_id: 462,
            };
            target_column = {
                id: 23,
            };
            angular.element($target_element).data("column-id", target_column.id);

            DroppedService.getComparedTo.mockReturnValue(compared_to);
            DroppedService.moveToColumn.mockReturnValue($q.when());
            ColumnCollectionService.getColumn.mockReturnValue(target_column);

            $scope.$emit(
                "dragulardrop",
                $dropped_item_element,
                $target_element,
                $source_element,
                source_model,
                initial_index,
                target_model,
                target_index
            );

            $scope.$apply();

            expect(DroppedService.moveToColumn).toHaveBeenCalledWith(
                current_kanban.id,
                target_column.id,
                dropped_item.id,
                compared_to,
                dropped_item.in_column
            );
            expect(KanbanColumnService.moveItem).toHaveBeenCalledWith(
                dropped_item,
                source_column,
                target_column,
                compared_to
            );

            expect($rootScope.$broadcast).toHaveBeenCalledWith("rebuild:kustom-scroll");
        });
    });

    describe("dragularDrag() -", function () {
        it("When I start dragging an item, then all wip edition dropdowns will be closed", function () {
            jest.spyOn(ColumnCollectionService, "cancelWipEditionOnAllColumns").mockImplementation(
                () => {}
            );
            KanbanColumnController.dragularOptions().onInit();

            $scope.$emit("dragulardrag");

            expect(ColumnCollectionService.cancelWipEditionOnAllColumns).toHaveBeenCalled();
        });
    });

    describe("isItemDraggable() -", function () {
        var $element_to_drag, $container, $handle_element;

        it("Given a handle element that had an ancestor with data-nodrag='true', when I check if it is draggable, then false will be returned", function () {
            const $parent_element = createElement("a", true);

            const handle = createElement("span", false);
            $handle_element = angular.element(handle);
            $parent_element.appendChild(handle);

            var result = KanbanColumnController.dragularOptions().moves(
                $element_to_drag,
                $container,
                $handle_element
            );

            expect(result).toBe(false);
        });

        it("Given a handle element that had itself data-nodrag='true', when I check if it is draggable, then false will be returned", function () {
            $handle_element = createElement("div", true);

            var result = KanbanColumnController.dragularOptions().moves(
                $element_to_drag,
                $container,
                $handle_element
            );

            expect(result).toBe(false);
        });

        it("Given a handle element that did not have any ancestor with data-nodrag='true', when I check if it is draggable, then true will be returned", function () {
            const $parent_element = createElement("div", false);
            $handle_element = createElement("span", false);
            $parent_element.appendChild($handle_element);

            var result = KanbanColumnController.dragularOptions().moves(
                $element_to_drag,
                $container,
                $handle_element
            );

            expect(result).toBe(true);
        });
    });

    describe("isColumnLoadedAndEmpty() -", function () {
        it("Given that the column was loading items, then false will be returned", function () {
            KanbanColumnController.column = {
                loading_items: true,
                content: [],
            };

            var result = KanbanColumnController.isColumnLoadedAndEmpty();

            expect(result).toBe(false);
        });

        it("Given that the column had some content in it, then false will be returned", function () {
            KanbanColumnController.column = {
                loading_items: false,
                content: [{ id: 573 }],
            };

            var result = KanbanColumnController.isColumnLoadedAndEmpty();

            expect(result).toBe(false);
        });

        it("Given that the column was not loading items and had no content in it, then true will be returned", function () {
            KanbanColumnController.column = {
                loading_items: false,
                content: [],
            };

            var result = KanbanColumnController.isColumnLoadedAndEmpty();

            expect(result).toBe(true);
        });
    });

    describe("cancelDrag() -", function () {
        it("When I cancel the drag, then dragular's cancel will be called and the custom 'appending_item' class will be removed", function () {
            var drake = {
                cancel: jest.fn(),
            };
            KanbanColumnController.drake = drake;

            KanbanColumnController.cancelDrag();

            expect(KanbanColumnController.appending_item).toBe(false);
            expect(KanbanColumnController.drake.cancel).toHaveBeenCalled();
        });
    });
});
