import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("KanbanColumnService -", () => {
    let $filter, $q, $rootScope, KanbanItemRestService, KanbanColumnService, KanbanFilterValue;

    beforeEach(() => {
        angular.mock.module(kanban_module, function ($provide) {
            $provide.decorator("$filter", function () {
                return jest.fn(() => () => []);
            });

            $provide.decorator("KanbanFilterValue", function () {
                return {
                    terms: "",
                };
            });

            $provide.decorator("KanbanItemRestService", function ($delegate) {
                jest.spyOn($delegate, "getItem").mockImplementation(() => {});

                return $delegate;
            });
        });

        angular.mock.inject(function (
            _$filter_,
            _$q_,
            _$rootScope_,
            _KanbanColumnService_,
            _KanbanFilterValue_,
            _KanbanItemRestService_
        ) {
            $filter = _$filter_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            KanbanColumnService = _KanbanColumnService_;
            KanbanFilterValue = _KanbanFilterValue_;
            KanbanItemRestService = _KanbanItemRestService_;
        });
    });

    describe("moveItem() -", function () {
        describe("Given an item to move, a source column object, a destination column object and a compared_to object", function () {
            var item, source_column, destination_column;
            beforeEach(function () {
                item = {
                    id: 27,
                    in_column: 4,
                    timeinfo: {
                        4: "some previous date",
                    },
                };

                source_column = {
                    id: 4,
                    is_open: true,
                    fully_loaded: true,
                    content: [{ id: 79 }, item, { id: 100 }],
                    filtered_content: [{ id: 79 }, item, { id: 100 }],
                };

                destination_column = {
                    id: 2,
                    is_open: true,
                    fully_loaded: true,
                    content: [{ id: 56 }, { id: 21 }],
                    filtered_content: [{ id: 56 }, { id: 21 }],
                };
            });

            describe("and given both columns were open and fully loaded", function () {
                it("and were unfiltered, when I move the item from the source column to the destination column, then the item's time and column properties will be updated and the source and destination columns will be updated", function () {
                    var compared_to = {
                        direction: "after",
                        item_id: 21,
                    };

                    KanbanColumnService.moveItem(
                        item,
                        source_column,
                        destination_column,
                        compared_to
                    );

                    expect(item.in_column).toBe(2);
                    expect(item.timeinfo[2]).toBeDefined();
                    expect(source_column.content).toEqual([{ id: 79 }, { id: 100 }]);
                    expect(source_column.filtered_content).toEqual([{ id: 79 }, { id: 100 }]);
                    expect(source_column.filtered_content).not.toBe(source_column.content);
                    expect(destination_column.content).toEqual([{ id: 56 }, { id: 21 }, item]);
                    expect(destination_column.filtered_content).toEqual([
                        { id: 56 },
                        { id: 21 },
                        item,
                    ]);
                    expect(destination_column.filtered_content).not.toBe(
                        destination_column.content
                    );
                });

                it("and were filtered and the item was in the filter, when I move the item from the source column to the destination column, then the item's time and column properties will be updated and the source and destination columns will be updated", function () {
                    var compared_to = {
                        direction: "before",
                        item_id: 56,
                    };
                    source_column.filtered_content = [item];
                    destination_column.filtered_content = [{ id: 21 }];

                    KanbanColumnService.moveItem(
                        item,
                        source_column,
                        destination_column,
                        compared_to
                    );

                    expect(item.in_column).toBe(2);
                    expect(item.timeinfo[2]).toBeDefined();
                    expect(source_column.content).toEqual([{ id: 79 }, { id: 100 }]);
                    expect(source_column.filtered_content).toEqual([]);
                    expect(source_column.filtered_content).not.toBe(source_column.content);
                    expect(destination_column.content).toEqual([item, { id: 56 }, { id: 21 }]);
                    expect(destination_column.filtered_content).toEqual([{ id: 21 }, item]);
                    expect(destination_column.filtered_content).not.toBe(
                        destination_column.content
                    );
                });
            });

            it("and given the source column was closed, when I move the item from the source column to the destination column, then the item's time and column properties will be updated and the source and destination columns will be updated", function () {
                var compared_to = {
                    direction: "after",
                    item_id: 21,
                };
                source_column.is_open = false;
                source_column.filtered_content = [];

                KanbanColumnService.moveItem(item, source_column, destination_column, compared_to);

                expect(item.in_column).toBe(2);
                expect(item.timeinfo[2]).toBeDefined();
                expect(source_column.content).toEqual([{ id: 79 }, { id: 100 }]);
                expect(source_column.filtered_content).toEqual([]);
                expect(source_column.filtered_content).not.toBe(source_column.content);
                expect(destination_column.content).toEqual([{ id: 56 }, { id: 21 }, item]);
                expect(destination_column.filtered_content).toEqual([{ id: 56 }, { id: 21 }, item]);
                expect(destination_column.filtered_content).not.toBe(destination_column.content);
            });

            it("and given the destination column was closed, when I move the item from the source column to the destination column, then the item's time and column properties will be updated and the source and destination columns will be updated", function () {
                var compared_to = {
                    direction: "before",
                    item_id: 56,
                };
                destination_column.is_open = false;
                destination_column.filtered_content = [];

                KanbanColumnService.moveItem(item, source_column, destination_column, compared_to);

                expect(item.in_column).toBe(2);
                expect(item.timeinfo[2]).toBeDefined();
                expect(source_column.content).toEqual([{ id: 79 }, { id: 100 }]);
                expect(source_column.filtered_content).toEqual([{ id: 79 }, { id: 100 }]);
                expect(source_column.filtered_content).not.toBe(source_column.content);
                expect(destination_column.content).toEqual([item, { id: 56 }, { id: 21 }]);
                expect(destination_column.filtered_content).toEqual([]);
                expect(destination_column.filtered_content).not.toBe(destination_column.content);
            });

            it("and given the destination column was closed and not fully loaded, when I move the item from the source column to the destination column, then the item's time and column properties will be updated, the source column will be updated and the destination's nb_items_at_kanban_init property will be updated", function () {
                var compared_to = {
                    direction: "after",
                    item_id: 21,
                };
                destination_column.is_open = false;
                destination_column.fully_loaded = false;
                destination_column.content = [];
                destination_column.filtered_content = [];
                destination_column.nb_items_at_kanban_init = 2;

                KanbanColumnService.moveItem(item, source_column, destination_column, compared_to);

                expect(item.in_column).toBe(2);
                expect(item.timeinfo[2]).toBeDefined();
                expect(source_column.content).toEqual([{ id: 79 }, { id: 100 }]);
                expect(source_column.filtered_content).toEqual([{ id: 79 }, { id: 100 }]);
                expect(source_column.filtered_content).not.toBe(source_column.content);
                expect(destination_column.content).toEqual([]);
                expect(destination_column.filtered_content).toEqual([]);
                expect(destination_column.filtered_content).not.toBe(destination_column.content);
                expect(destination_column.nb_items_at_kanban_init).toBe(3);
            });

            it("and given the source column was closed and not fully loaded, when I move the item from the source column to the destination column, then the item's time and column properties will be updated, the source column's nb_items_at_kanban_init property will be updated and the destination column will be updated", function () {
                var compared_to = {
                    direction: "after",
                    item_id: 56,
                };
                source_column.is_open = false;
                source_column.fully_loaded = false;
                source_column.content = [];
                source_column.filtered_content = [];
                source_column.nb_items_at_kanban_init = 3;

                KanbanColumnService.moveItem(item, source_column, destination_column, compared_to);

                expect(item.in_column).toBe(2);
                expect(item.timeinfo[2]).toBeDefined();
                expect(source_column.content).toEqual([]);
                expect(source_column.filtered_content).toEqual([]);
                expect(source_column.filtered_content).not.toBe(source_column.content);
                expect(source_column.nb_items_at_kanban_init).toBe(2);
                expect(destination_column.content).toEqual([{ id: 56 }, item, { id: 21 }]);
                expect(destination_column.filtered_content).toEqual([{ id: 56 }, item, { id: 21 }]);
                expect(destination_column.filtered_content).not.toBe(destination_column.content);
            });

            it("and given the item was already in the destination column's filtered content (because it was drag and dropped there), when I move the item from the source column to the destination column, then the item will not exist twice in the destination column", function () {
                var compared_to = {
                    direction: "after",
                    item_id: 21,
                };
                destination_column.filtered_content = [{ id: 56 }, { id: 21 }, item];

                KanbanColumnService.moveItem(item, source_column, destination_column, compared_to);

                expect(destination_column.filtered_content).toEqual([{ id: 56 }, { id: 21 }, item]);
            });

            it("and given the source column was the backlog, when I move the item from the backlog to the destination column, then the item's kanban time info will also be updated", function () {
                var compared_to = {
                    direction: "after",
                    item_id: 56,
                };
                item.in_column = "backlog";
                item.timeinfo = {
                    backlog: "some previous date",
                };

                KanbanColumnService.moveItem(item, source_column, destination_column, compared_to);

                expect(item.in_column).toBe(2);
                expect(item.timeinfo.kanban).toBeDefined();
                expect(item.timeinfo.backlog).toBeDefined();
            });
        });

        it("Given an item to move, a source column object, a destination column object and NO compared_to object, when I move the item from the source column to the destination column, then the item's time and column properties will be updated, the item will be removed from the source column and appended to the destination column", function () {
            var item = {
                id: 90,
                in_column: 8,
                timeinfo: {
                    8: "some previous date",
                },
            };

            var source_column = {
                id: 8,
                is_open: true,
                fully_loaded: true,
                content: [item, { id: 10 }, { id: 88 }],
                filtered_content: [item, { id: 10 }, { id: 88 }],
            };

            var destination_column = {
                id: "archive",
                is_open: true,
                fully_loaded: true,
                content: [],
                filtered_content: [],
            };

            var compared_to = null;

            KanbanColumnService.moveItem(item, source_column, destination_column, compared_to);

            expect(source_column.content).toEqual([{ id: 10 }, { id: 88 }]);
            expect(source_column.filtered_content).toEqual([{ id: 10 }, { id: 88 }]);
            expect(source_column.filtered_content).not.toBe(source_column.content);
            expect(destination_column.content).toEqual([item]);
            expect(destination_column.filtered_content).toEqual([item]);
            expect(destination_column.filtered_content).not.toBe(destination_column.content);
        });
    });

    describe("filterItems() -", function () {
        it("Given filter terms that did not match anything, when I filter a column's items, then the InPropertiesFilter will be called and the column's filtered content collection will be emptied", function () {
            var column = {
                content: [{ id: 37 }],
                filtered_content: [{ id: 37 }],
            };
            var filtered_content_ref = column.filtered_content;

            KanbanFilterValue.terms = "reagreement";
            KanbanColumnService.filterItems(column);

            expect($filter).toHaveBeenCalledWith("InPropertiesFilter");
            expect(column.filtered_content).toBe(filtered_content_ref);
            expect(column.filtered_content).toHaveLength(0);
        });

        it("Given filter terms that matched items, when I filter a column's items, then the InPropertiesFilter will be called and the column's filtered content collection will be updated", function () {
            var column = {
                content: [{ id: 46 }, { id: 37 }, { id: 62 }],
                filtered_content: [{ id: 46 }, { id: 37 }, { id: 62 }],
            };
            $filter.mockImplementation(function () {
                return function () {
                    return [{ id: 46 }, { id: 62 }];
                };
            });

            KanbanFilterValue.terms = "6";
            KanbanColumnService.filterItems(column);

            expect($filter).toHaveBeenCalledWith("InPropertiesFilter");
            expect(column.filtered_content).toEqual([{ id: 46 }, { id: 62 }]);
        });
    });

    describe("findItemAndReorderItems()", function () {
        it("Given an item id, a source column and a destination column, when I move an item from a column not loaded, then the item REST route is called", function () {
            const item = {
                id: 50,
            };
            const source_column = {
                content: [],
            };
            const destination_column = {
                content: [{ id: 46 }, { id: 37 }, { id: 62 }],
                is_open: false,
                fully_loaded: false,
                filtered_content: [],
            };

            KanbanItemRestService.getItem.mockReturnValue($q.when(item));

            jest.spyOn(KanbanColumnService, "moveItem").mockImplementation(() => {});
            jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(() => {});

            KanbanColumnService.findItemAndReorderItems(
                50,
                source_column,
                destination_column,
                [46, 50, 37, 62]
            );
            $rootScope.$apply();

            expect(KanbanItemRestService.getItem).toHaveBeenCalledWith(50);
            expect(KanbanColumnService.moveItem).toHaveBeenCalledWith(
                item,
                source_column,
                destination_column,
                null
            );
            expect(KanbanColumnService.filterItems).not.toHaveBeenCalledWith(destination_column);
            expect(destination_column.filtered_content).toEqual([]);
            expect(destination_column.filtered_content).not.toBe(destination_column.content);
        });

        it("Given an item id, a source column and a destination column, when I move an item from a column loaded, then the item REST route is not called", function () {
            const item = {
                id: 50,
            };
            const source_column = {
                content: [item],
            };
            const destination_column = {
                content: [{ id: 46 }, { id: 37 }, { id: 62 }],
                is_open: true,
                fully_loaded: true,
                filtered_content: [{ id: 46 }, { id: 37 }, { id: 62 }],
            };
            jest.spyOn(KanbanColumnService, "moveItem").mockImplementation(() => {});
            jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(() => {});

            KanbanColumnService.findItemAndReorderItems(
                50,
                source_column,
                destination_column,
                [46, 50, 37, 62]
            );
            $rootScope.$apply();

            expect(KanbanColumnService.moveItem).toHaveBeenCalledWith(
                item,
                source_column,
                destination_column,
                null
            );
            expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(destination_column);
            expect(KanbanItemRestService.getItem).not.toHaveBeenCalled();
            expect(destination_column.filtered_content).toEqual(destination_column.content);
        });

        it("Given an item id, a source column and a destination column, when I move an item from a column not loaded, then the item is added to the new", function () {
            const item = {
                id: 50,
            };
            const source_column = {
                content: [],
                fully_loaded: false,
            };
            const destination_column = {
                content: [{ id: 46 }, { id: 37 }, { id: 62 }],
                is_open: true,
                fully_loaded: true,
                filtered_content: [{ id: 46 }, { id: 37 }, { id: 62 }],
            };

            KanbanItemRestService.getItem.mockReturnValue($q.when(item));

            KanbanColumnService.findItemAndReorderItems(
                50,
                source_column,
                destination_column,
                [46, 50, 37, 62]
            );
            $rootScope.$apply();
            expect(source_column.content).toEqual([]);
            expect(destination_column.content.map((item) => item.id)).toEqual([46, 50, 37, 62]);
        });

        it("Given an item id, a source column and a destination column, when I move an item from a column loaded, then the item is removed from old column and added to the new", function () {
            const item = {
                id: 50,
            };
            const source_column = {
                content: [item],
                fully_loaded: true,
            };
            const destination_column = {
                content: [{ id: 46 }, { id: 37 }, { id: 62 }],
                is_open: true,
                fully_loaded: true,
                filtered_content: [{ id: 46 }, { id: 37 }, { id: 62 }],
            };

            KanbanColumnService.findItemAndReorderItems(
                50,
                source_column,
                destination_column,
                [46, 50, 37, 62]
            );
            $rootScope.$apply();
            expect(source_column.content).toEqual([]);
            expect(destination_column.content.map((item) => item.id)).toEqual([46, 50, 37, 62]);
        });
    });

    describe("updateItemContent()", () => {
        it("Given an original item and an updated item, then the original item will only have some of its properties changed", () => {
            const item = {
                id: 17,
                background_color_name: "avocado_featherback",
                color: "hedging_enteria",
                item_name: "avicide",
                label: "Squamscot",
                card_fields: [{ field_id: 60, label: "Marssonina", value: "" }],
                in_column: "archive",
                is_collapsed: true,
                updating: true,
                timeinfo: {},
            };

            const updated_item = {
                id: 17,
                background_color_name: "cheddaring_permutability",
                color: "executionist_holosteum",
                item_name: "sort",
                label: "wheem",
                in_column: "archive",
                card_fields: [
                    { field_id: 60, label: "Marssonina", value: "downgrowth" },
                    { field_id: 57, label: "suffect", value: 51 },
                ],
            };

            KanbanColumnService.updateItemContent(item, updated_item);

            expect(item).toEqual({
                id: 17,
                background_color_name: "cheddaring_permutability",
                color: "executionist_holosteum",
                item_name: "sort",
                label: "wheem",
                card_fields: [
                    { field_id: 60, label: "Marssonina", value: "downgrowth" },
                    { field_id: 57, label: "suffect", value: 51 },
                ],
                in_column: "archive",
                is_collapsed: true,
                updating: true,
                timeinfo: {
                    archive: expect.any(Date),
                },
            });
        });

        it("When the item is moved from the backlog, then its time info will be updated", () => {
            const item = {
                id: 30,
                in_column: "backlog",
                timeinfo: {},
            };

            const updated_item = {
                id: 30,
                in_column: 28,
            };

            KanbanColumnService.updateItemContent(item, updated_item);

            expect(item.in_column).toBe(28);
            expect(item.timeinfo).toEqual({
                kanban: expect.any(Date),
                28: expect.any(Date),
            });
        });

        it("When the item is moved from any other column, then its time info will be updated", () => {
            const item = {
                id: 10,
                in_column: 45,
                timeinfo: {},
            };

            const updated_item = {
                id: 10,
                in_column: "backlog",
            };

            KanbanColumnService.updateItemContent(item, updated_item);

            expect(item.in_column).toBe("backlog");
            expect(item.timeinfo).toEqual({
                backlog: expect.any(Date),
            });
        });
    });
});
