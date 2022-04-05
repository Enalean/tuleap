import kanban_module from "./app.js";
import angular from "angular";
import "angular-mocks";

describe("ColumnCollectionService -", function () {
    var ColumnCollectionService, SharedPropertiesService;

    beforeEach(function () {
        angular.mock.module(kanban_module, function ($provide) {
            $provide.decorator("SharedPropertiesService", function ($delegate) {
                jest.spyOn($delegate, "getKanban").mockImplementation(() => {});

                return $delegate;
            });
        });

        angular.mock.inject(function (_ColumnCollectionService_, _SharedPropertiesService_) {
            ColumnCollectionService = _ColumnCollectionService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });
    });

    describe("getColumn() -", function () {
        it("Given the id 'archive', when I get this column, then the archive model object will be returned", function () {
            var archive = {
                id: "archive",
                content: [],
            };
            SharedPropertiesService.getKanban.mockReturnValue({
                archive: archive,
            });

            var result = ColumnCollectionService.getColumn("archive");

            expect(result).toBe(archive);
        });

        it("Given the id 'backlog', when I get this column, then the backlog model object will be returned", function () {
            var backlog = {
                id: "backlog",
                content: [],
            };
            SharedPropertiesService.getKanban.mockReturnValue({
                backlog: backlog,
            });

            var result = ColumnCollectionService.getColumn("backlog");

            expect(result).toBe(backlog);
        });

        it("Given a numeric id of a column, when I get this column, then the column's model object will be returned", function () {
            var column = {
                id: 68,
                content: [],
            };
            SharedPropertiesService.getKanban.mockReturnValue({
                columns: [column],
            });

            var result = ColumnCollectionService.getColumn(68);

            expect(result).toBe(column);
        });
    });

    describe("cancelWipEditionOnAllColumns", function () {
        it("when I cancel the wip edition on all columns, then the 'wip_in_edit' property will be set to false for all columns except archive and backlog", function () {
            var first_column = {
                wip_in_edit: true,
            };
            var second_column = {
                wip_in_edit: true,
            };
            SharedPropertiesService.getKanban.mockReturnValue({
                columns: [first_column, second_column],
            });

            ColumnCollectionService.cancelWipEditionOnAllColumns();

            expect(first_column.wip_in_edit).toBe(false);
            expect(second_column.wip_in_edit).toBe(false);
        });
    });

    describe("addColumn()", function () {
        it("Given a column, when I add a column, then the column is added on kanban's columns", function () {
            var column = {
                id: 68,
                content: [],
            };
            var column_to_add = {
                id: 69,
                content: [],
            };
            SharedPropertiesService.getKanban.mockReturnValue({
                columns: [column],
            });

            ColumnCollectionService.addColumn(column_to_add);

            expect(SharedPropertiesService.getKanban().columns).toHaveLength(2);
        });
    });

    describe("removeColumn()", function () {
        it("Given an column id, when I remove a column, then the column is removed on kanban's columns", function () {
            var column = {
                id: 68,
                content: [],
            };
            var column_to_remove = {
                id: 69,
                content: [],
            };
            SharedPropertiesService.getKanban.mockReturnValue({
                columns: [column, column_to_remove],
            });

            ColumnCollectionService.removeColumn(69);

            expect(SharedPropertiesService.getKanban().columns).toEqual([column]);
        });
    });

    describe("reorderColumns()", function () {
        it("Given columns, when I reorder columns, then kanban's columns are updated", function () {
            var first_column = {
                id: 68,
                content: [],
            };
            var second_column = {
                id: 69,
                content: [],
            };
            SharedPropertiesService.getKanban.mockReturnValue({
                columns: [first_column, second_column],
            });

            ColumnCollectionService.reorderColumns([69, 68]);

            expect(SharedPropertiesService.getKanban().columns).toEqual([
                second_column,
                first_column,
            ]);
        });
    });

    describe("findItemById()", function () {
        it("Given an item id, when I find the existing item in all columns, then the item returned", function () {
            var item = {
                id: 50,
            };
            var columns = [
                {
                    id: 68,
                    content: [item],
                },
            ];
            var backlog = {
                content: [{ id: 46 }, { id: 37 }, { id: 62 }],
            };
            var archive = {
                content: [{ id: 46 }, { id: 37 }, { id: 62 }],
            };

            SharedPropertiesService.getKanban.mockReturnValue({
                columns: columns,
                backlog: backlog,
                archive: archive,
            });

            expect(ColumnCollectionService.findItemById(50)).toBe(item);
        });

        it("Given an item id, when I find item in columns and it doesn't exists, then the item ins't returned", function () {
            var columns = [
                {
                    content: [],
                },
            ];
            var backlog = {
                content: [{ id: 46 }, { id: 37 }, { id: 62 }],
            };
            var archive = {
                content: [{ id: 46 }, { id: 37 }, { id: 62 }],
            };

            SharedPropertiesService.getKanban.mockReturnValue({
                columns: columns,
                backlog: backlog,
                archive: archive,
            });

            expect(ColumnCollectionService.findItemById(60)).toBeNull();
        });
    });
});
