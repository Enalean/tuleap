import kanban_module from "./app.js";
import angular from "angular";
import "angular-mocks";

describe("DroppedService -", function() {
    var DroppedService, KanbanService;

    beforeEach(function() {
        angular.mock.module("kanban", function($provide) {
            $provide.decorator("KanbanService", function($delegate, $q) {
                spyOn($delegate, "moveInArchive").and.returnValue($q.when());
                spyOn($delegate, "moveInBacklog").and.returnValue($q.when());
                spyOn($delegate, "moveInColumn").and.returnValue($q.when());
                spyOn($delegate, "reorderArchive").and.returnValue($q.when());
                spyOn($delegate, "reorderBacklog").and.returnValue($q.when());
                spyOn($delegate, "reorderColumn").and.returnValue($q.when());

                return $delegate;
            });
        });

        angular.mock.inject(function(_DroppedService_, _KanbanService_) {
            DroppedService = _DroppedService_;
            KanbanService = _KanbanService_;
        });

        installPromiseMatchers();
    });

    describe("moveToColumn() -", function() {
        var kanban_id, kanban_item_id, column_id, compared_to, from_column;

        beforeEach(function() {
            kanban_id = 1;
            kanban_item_id = 997;
            compared_to = {
                direction: "after",
                item_id: 423
            };
            from_column = 912;
        });

        it("Given a kanban id, a numeric column id, a kanban item id, and a compared_to object, when I move the kanban item to the column, then KanbanService.moveInColumn will be called and a promise will be resolved", function() {
            column_id = 33;

            var promise = DroppedService.moveToColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to,
                from_column
            );

            expect(promise).toBeResolved();
            expect(KanbanService.moveInColumn).toHaveBeenCalledWith(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to,
                from_column
            );
        });

        it("Given 'backlog' as a column id, when I move the kanban item to the backlog, then KanbanService.moveInBacklog will be called and a promise will be resolved", function() {
            column_id = "backlog";

            var promise = DroppedService.moveToColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to,
                from_column
            );

            expect(promise).toBeResolved();
            expect(KanbanService.moveInBacklog).toHaveBeenCalledWith(
                kanban_id,
                kanban_item_id,
                compared_to,
                from_column
            );
        });

        it("Given 'archive' as a column id, when I move the kanban item to the archive, then KanbanService.moveInArchive will be called and a promise will be resolved", function() {
            column_id = "archive";

            var promise = DroppedService.moveToColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to,
                from_column
            );

            expect(promise).toBeResolved();
            expect(KanbanService.moveInArchive).toHaveBeenCalledWith(
                kanban_id,
                kanban_item_id,
                compared_to,
                from_column
            );
        });
    });

    describe("reorderColumn() -", function() {
        var kanban_id, kanban_item_id, column_id, compared_to;

        beforeEach(function() {
            kanban_id = 3;
            kanban_item_id = 367;
            compared_to = {
                direction: "before",
                item_id: 539
            };
        });

        it("Given a kanban id, a numeric column id, a kanban item id, and a compared_to object, when I reorder the kanban item in the same column, then KanbanService.reorderColumn will be called and a promise will be resolved", function() {
            column_id = 22;

            var promise = DroppedService.reorderColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to
            );

            expect(promise).toBeResolved();
            expect(KanbanService.reorderColumn).toHaveBeenCalledWith(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to
            );
        });

        it("Given 'backlog' as a column id, when I reorder the kanban item in the backlog, then KanbanService.reorderBacklog will be called and a promise will be resolved", function() {
            column_id = "backlog";

            var promise = DroppedService.reorderColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to
            );

            expect(promise).toBeResolved();
            expect(KanbanService.reorderBacklog).toHaveBeenCalledWith(
                kanban_id,
                kanban_item_id,
                compared_to
            );
        });

        it("Given 'archive' as a column id, when I reorder the kanban item in the archive, then KanbanService.reorderArchive will be called and a promise will be resolved", function() {
            column_id = "archive";

            var promise = DroppedService.reorderColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to
            );

            expect(promise).toBeResolved();
            expect(KanbanService.reorderArchive).toHaveBeenCalledWith(
                kanban_id,
                kanban_item_id,
                compared_to
            );
        });
    });

    describe("getComparedTo() -", function() {
        it("Given an empty item list, when I drop an item in it, then null will be returned", function() {
            var item_list = [{ id: 687 }];
            var index = 0;

            var compared_to = DroppedService.getComparedTo(item_list, index);

            expect(compared_to).toBe(null);
        });

        it("Given an item list, when I drop an item before its first element, then an object with direction 'before' and item_id equal to the id of the second element will be returned ", function() {
            var item_list = [{ id: 996 }, { id: 743 }];
            var index = 0;

            var compared_to = DroppedService.getComparedTo(item_list, index);

            expect(compared_to).toEqual({
                direction: "before",
                item_id: 743
            });
        });

        it("Given an item list, when I drop an item after its second element, then an object with direction 'after' and item_id equal to the id of the second element will be returned", function() {
            var item_list = [{ id: 386 }, { id: 896 }, { id: 255 }];
            var index = 2;

            var compared_to = DroppedService.getComparedTo(item_list, index);

            expect(compared_to).toEqual({
                direction: "after",
                item_id: 896
            });
        });
    });

    describe("getComparedToBeFirstItemOfColumn() -", function() {
        it("Given an empty column, when I move an item to be first of it, then null will be returned", function() {
            var column = {
                content: []
            };

            var compared_to = DroppedService.getComparedToBeFirstItemOfColumn(column);

            expect(compared_to).toBe(null);
        });

        it("Given a column, when I move an item to be first of it, then an object with direction 'before' and item_id equal to the id of the current first element will be returned", function() {
            var column = {
                content: [{ id: 398 }, { id: 952 }]
            };

            var compared_to = DroppedService.getComparedToBeFirstItemOfColumn(column);

            expect(compared_to).toEqual({
                direction: "before",
                item_id: 398
            });
        });
    });

    describe("getComparedToBeLastItemOfColumn() -", function() {
        it("Given an empty column, when I move an item to be last of it, then null will be returned", function() {
            var column = {
                content: []
            };

            var compared_to = DroppedService.getComparedToBeLastItemOfColumn(column);

            expect(compared_to).toBe(null);
        });

        it("Given a column, when I move an item to be last of it, then an object with direction 'after' and item_id equal to the id of the current last element will be returned", function() {
            var column = {
                content: [{ id: 289 }, { id: 204 }]
            };

            var compared_to = DroppedService.getComparedToBeLastItemOfColumn(column);

            expect(compared_to).toEqual({
                direction: "after",
                item_id: 204
            });
        });
    });
});
