/*
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import kanban_module from "./app.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./app-kanban-controller.js";
import io from "socket.io-client";

jest.mock("socket.io-client");

describe("KanbanCtrl", function () {
    let $rootScope,
        $scope,
        $controller,
        $q,
        KanbanCtrl,
        SharedPropertiesService,
        KanbanService,
        KanbanColumnService,
        KanbanItemRestService,
        NewTuleapArtifactModalService,
        SocketService,
        DroppedService,
        ColumnCollectionService,
        UserPreferencesService,
        kanban;

    const user_id = 757;
    function emptyArray(array) {
        array.length = 0;
    }

    beforeEach(function () {
        io.mockReturnValue({
            on: () => {
                // Empty mock on purpose
            },
        });

        angular.mock.module(kanban_module);

        angular.mock.inject(
            function (
                _$controller_,
                _$q_,
                _$rootScope_,
                _KanbanItemRestService_,
                _KanbanService_,
                _NewTuleapArtifactModalService_,
                _SharedPropertiesService_,
                _KanbanColumnService_,
                _SocketService_,
                _DroppedService_,
                _ColumnCollectionService_,
                _UserPreferencesService_,
            ) {
                $controller = _$controller_;
                $q = _$q_;
                $rootScope = _$rootScope_;
                KanbanColumnService = _KanbanColumnService_;
                KanbanItemRestService = _KanbanItemRestService_;
                KanbanService = _KanbanService_;
                NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
                SharedPropertiesService = _SharedPropertiesService_;
                SocketService = _SocketService_;
                DroppedService = _DroppedService_;
                ColumnCollectionService = _ColumnCollectionService_;
                UserPreferencesService = _UserPreferencesService_;
            },
        );

        kanban = {
            id: 38,
            label: "",
            archive: {},
            backlog: {},
            columns: [{ id: 230 }, { id: 530 }],
            tracker_id: 56,
        };

        jest.spyOn(SharedPropertiesService, "getKanban").mockReturnValue(kanban);
        jest.spyOn(SharedPropertiesService, "getUserId").mockReturnValue(user_id);

        jest.spyOn(KanbanService, "getBacklog").mockReturnValue($q.defer().promise);
        jest.spyOn(KanbanService, "getBacklogSize").mockReturnValue($q.defer().promise);
        jest.spyOn(KanbanService, "getArchive").mockReturnValue($q.defer().promise);
        jest.spyOn(KanbanService, "getArchiveSize").mockReturnValue($q.defer().promise);
        jest.spyOn(KanbanService, "getColumnContentSize").mockReturnValue($q.defer().promise);

        jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(() => {});
        jest.spyOn(KanbanColumnService, "moveItem").mockImplementation(() => {});
        jest.spyOn(DroppedService, "getComparedTo").mockImplementation(() => {});
        jest.spyOn(DroppedService, "getComparedToBeFirstItemOfColumn").mockImplementation(() => {});
        jest.spyOn(DroppedService, "getComparedToBeLastItemOfColumn").mockImplementation(() => {});
        jest.spyOn(DroppedService, "reorderColumn").mockReturnValue($q.when());
        jest.spyOn(DroppedService, "moveToColumn").mockReturnValue($q.when());
        jest.spyOn(ColumnCollectionService, "getColumn").mockImplementation(() => {});
        jest.spyOn(SocketService, "listenNodeJSServer").mockReturnValue($q.defer().promise);
        jest.spyOn(SocketService, "open").mockImplementation(() => {});

        $scope = $rootScope.$new();

        KanbanCtrl = $controller(BaseController, {
            $scope: $scope,
            $q: $q,
            SharedPropertiesService: SharedPropertiesService,
            KanbanService: KanbanService,
            KanbanItemRestService: KanbanItemRestService,
            NewTuleapArtifactModalService: NewTuleapArtifactModalService,
            KanbanColumnService: KanbanColumnService,
            SocketService: SocketService,
            ColumnCollectionService: ColumnCollectionService,
            UserPreferencesService: UserPreferencesService,
        });
    });

    describe("init() -", function () {
        describe("loadArchive() -", function () {
            it("Given that the archive column was open, when I load it, then its content will be loaded and filtered", function () {
                KanbanCtrl.archive.is_open = true;
                var get_archive_request = $q.defer();
                KanbanService.getArchive.mockReturnValue(get_archive_request.promise);

                KanbanCtrl.$onInit();

                expect(KanbanCtrl.archive.loading_items).toBeTruthy();
                expect(KanbanService.getArchive).toHaveBeenCalledWith(kanban.id, 50, 0);

                get_archive_request.resolve({
                    results: [{ id: 88 }, { id: 40 }],
                });
                $scope.$apply();

                expect(KanbanCtrl.archive.content).toStrictEqual([{ id: 88 }, { id: 40 }]);
                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(KanbanCtrl.archive);
                expect(KanbanCtrl.archive.loading_items).toBeFalsy();
                expect(KanbanCtrl.archive.fully_loaded).toBeTruthy();
            });

            it("Given that the archive column was closed, when I load it, then only its total number of items will be loaded", function () {
                var get_archive_size_request = $q.defer();
                KanbanService.getArchiveSize.mockReturnValue(get_archive_size_request.promise);

                KanbanCtrl.archive.is_open = false;

                KanbanCtrl.$onInit();
                get_archive_size_request.resolve(6);
                $scope.$apply();

                expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
                expect(KanbanService.getArchiveSize).toHaveBeenCalledWith(kanban.id);
                expect(KanbanCtrl.archive.loading_items).toBeFalsy();
                expect(KanbanCtrl.archive.nb_items_at_kanban_init).toBe(6);
            });
        });

        describe("loadBacklog() -", function () {
            it("Given that the backlog column was open, when I load it, then its content will be loaded", function () {
                KanbanCtrl.backlog.is_open = true;
                var get_backlog_request = $q.defer();
                KanbanService.getBacklog.mockReturnValue(get_backlog_request.promise);

                KanbanCtrl.$onInit();

                expect(KanbanCtrl.backlog.loading_items).toBeTruthy();
                expect(KanbanService.getBacklog).toHaveBeenCalledWith(kanban.id, 50, 0);

                get_backlog_request.resolve({
                    results: [{ id: 69 }, { id: 16 }],
                });
                $scope.$apply();

                expect(KanbanCtrl.backlog.content).toStrictEqual([{ id: 69 }, { id: 16 }]);
                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(KanbanCtrl.backlog);
                expect(KanbanCtrl.backlog.loading_items).toBeFalsy();
                expect(KanbanCtrl.backlog.fully_loaded).toBeTruthy();
            });

            it("Given that the backlog column was closed, when I load it, then only its total number of items will be loaded", function () {
                var get_backlog_size_request = $q.defer();
                KanbanService.getBacklogSize.mockReturnValue(get_backlog_size_request.promise);

                KanbanCtrl.backlog.is_open = false;

                KanbanCtrl.$onInit();
                get_backlog_size_request.resolve(28);
                $scope.$apply();

                expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
                expect(KanbanService.getBacklogSize).toHaveBeenCalledWith(kanban.id);
                expect(KanbanCtrl.backlog.loading_items).toBeFalsy();
                expect(KanbanCtrl.backlog.nb_items_at_kanban_init).toBe(28);
            });
        });

        describe("loadColumns() -", function () {
            it("Given a kanban column that was open, when I load it, then its content will be loaded", function () {
                var get_column_request = $q.defer();
                jest.spyOn(KanbanService, "getItems").mockReturnValue(get_column_request.promise);
                kanban.columns = [];
                kanban.columns[0] = {
                    id: 10,
                    label: "palate",
                    limit: 7,
                    is_open: true,
                };

                KanbanCtrl.$onInit();

                var column = kanban.columns[0];
                expect(column.content).toStrictEqual([]);
                expect(column.filtered_content).toStrictEqual([]);
                expect(column.filtered_content).not.toBe(column.content);
                expect(column.loading_items).toBeTruthy();
                expect(column.nb_items_at_kanban_init).toBe(0);
                expect(column.fully_loaded).toBeFalsy();
                expect(column.wip_in_edit).toBeFalsy();
                expect(column.limit_input).toBe(7);
                expect(column.saving_wip).toBeFalsy();
                expect(column.is_defered).toBeFalsy();
                expect(column.original_label).toBe("palate");

                expect(KanbanService.getItems).toHaveBeenCalledWith(kanban.id, column.id, 50, 0);
                get_column_request.resolve({
                    results: [{ id: 981 }, { id: 331 }],
                });
                $scope.$apply();

                expect(column.content).toStrictEqual([{ id: 981 }, { id: 331 }]);
                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(column);
                expect(column.loading_items).toBeFalsy();
                expect(column.fully_loaded).toBeTruthy();
            });

            it("Given a kanban column that was closed, when I load it, then only its total number of items will be loaded", function () {
                var get_column_size_request = $q.defer();
                KanbanService.getColumnContentSize.mockReturnValue(get_column_size_request.promise);
                kanban.columns = [];
                kanban.columns[0] = {
                    id: 75,
                    label: "undisfranchised",
                    limit: 21,
                    is_open: false,
                };

                KanbanCtrl.$onInit();

                var column = kanban.columns[0];
                expect(column.content).toStrictEqual([]);
                expect(column.filtered_content).toStrictEqual([]);
                expect(column.filtered_content).not.toBe(column.content);
                expect(column.loading_items).toBeTruthy();
                expect(column.nb_items_at_kanban_init).toBe(0);
                expect(column.fully_loaded).toBeFalsy();
                expect(column.wip_in_edit).toBeFalsy();
                expect(column.limit_input).toBe(21);
                expect(column.saving_wip).toBeFalsy();
                expect(column.is_defered).toBeTruthy();
                expect(column.original_label).toBe("undisfranchised");

                KanbanCtrl.$onInit();
                get_column_size_request.resolve(42);
                $scope.$apply();

                expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
                expect(KanbanService.getColumnContentSize).toHaveBeenCalledWith(
                    kanban.id,
                    column.id,
                );
                expect(column.loading_items).toBeFalsy();
                expect(column.nb_items_at_kanban_init).toBe(42);
            });
        });
    });

    describe("toggleArchive() -", function () {
        it("Given that the archive column was open, when I toggle it, then it will be collapsed and its filtered content will be emptied", function () {
            jest.spyOn(KanbanService, "collapseArchive").mockImplementation(() => {});
            KanbanCtrl.archive.is_open = true;
            KanbanCtrl.archive.filtered_content = [{ id: 82 }];

            KanbanCtrl.toggleArchive();

            expect(KanbanCtrl.archive.filtered_content).toStrictEqual([]);
            expect(KanbanService.collapseArchive).toHaveBeenCalledWith(kanban.id);
            expect(KanbanCtrl.archive.is_open).toBeFalsy();
        });

        describe("Given that the archive column was closed", function () {
            beforeEach(() => {
                KanbanCtrl.archive.is_open = false;
                jest.spyOn(KanbanService, "expandArchive").mockImplementation(() => {});
            });

            it("and fully loaded, when I toggle it, then it will be expanded and filtered", function () {
                KanbanCtrl.archive.fully_loaded = true;
                KanbanCtrl.archive.content = [{ id: 36 }];

                KanbanCtrl.toggleArchive();

                expect(KanbanService.expandArchive).toHaveBeenCalledWith(kanban.id);
                expect(KanbanCtrl.archive.is_open).toBeTruthy();
                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(KanbanCtrl.archive);
            });

            it("and not yet loaded, when I toggle it, then it will be expanded and loaded", function () {
                KanbanCtrl.archive.fully_loaded = false;

                KanbanCtrl.toggleArchive();

                expect(KanbanService.getArchive).toHaveBeenCalled();
            });
        });
    });

    describe("toggleBacklog() -", function () {
        it("Given that the backlog column was open, when I toggle it, then it will be collapsed and its filtered content will be emptied", function () {
            jest.spyOn(KanbanService, "collapseBacklog").mockImplementation(() => {});
            KanbanCtrl.backlog.is_open = true;
            KanbanCtrl.backlog.filtered_content = [{ id: 70 }];

            KanbanCtrl.toggleBacklog();

            expect(KanbanCtrl.backlog.filtered_content).toStrictEqual([]);
            expect(KanbanService.collapseBacklog).toHaveBeenCalledWith(kanban.id);
            expect(KanbanCtrl.backlog.is_open).toBeFalsy();
        });

        describe("Given that the backlog column was closed", function () {
            beforeEach(() => {
                KanbanCtrl.backlog.is_open = false;
                jest.spyOn(KanbanService, "expandBacklog").mockImplementation(() => {});
            });

            it("and fully loaded, when I toggle it, then it will be expanded and filtered", function () {
                KanbanCtrl.backlog.fully_loaded = true;
                KanbanCtrl.backlog.content = [{ id: 80 }];

                KanbanCtrl.toggleBacklog();

                expect(KanbanService.expandBacklog).toHaveBeenCalledWith(kanban.id);
                expect(KanbanCtrl.backlog.is_open).toBeTruthy();
                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(KanbanCtrl.backlog);
            });

            it("and not yet loaded, when I toggle it, then it will be expanded and loaded", function () {
                KanbanCtrl.backlog.fully_loaded = false;

                KanbanCtrl.toggleBacklog();

                expect(KanbanService.getBacklog).toHaveBeenCalled();
            });
        });
    });

    describe("toggleColumn() -", function () {
        it("Given a kanban column that was open, when I toggle it, then it will be collapsed and its filtered content will be emptied", function () {
            jest.spyOn(KanbanService, "collapseColumn").mockImplementation(() => {});
            var column = {
                id: 22,
                is_open: true,
                filtered_content: [{ id: 25 }],
            };

            KanbanCtrl.toggleColumn(column);

            expect(column.filtered_content).toStrictEqual([]);
            expect(KanbanService.collapseColumn).toHaveBeenCalledWith(kanban.id, column.id);
            expect(column.is_open).toBeFalsy();
        });

        describe("Given a kanban column that was closed", function () {
            var column;
            beforeEach(function () {
                column = {
                    id: 69,
                    is_open: false,
                };
            });

            it("and fully loaded, when I toggle it, then it will be expanded and filtered", function () {
                jest.spyOn(KanbanService, "expandColumn").mockImplementation(() => {});
                column.fully_loaded = true;
                column.content = [{ id: 81 }];

                KanbanCtrl.toggleColumn(column);

                expect(KanbanService.expandColumn).toHaveBeenCalledWith(kanban.id, column.id);
                expect(column.is_open).toBeTruthy();
                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(column);
            });
        });
    });

    describe("createItemInPlace", () => {
        it(`Given a label and a kanban column,
            when I create a new kanban item,
            then it will be created using KanbanItemRestService
            and will be appended to the given column`, () => {
            jest.spyOn(SharedPropertiesService, "doesUserPrefersCompactCards").mockReturnValue(
                true,
            );
            jest.spyOn(KanbanItemRestService, "createItem").mockReturnValue(
                $q.when({ id: 94, label: "photothermic" }),
            );
            const column = {
                id: 5,
                content: [{ id: 97 }, { id: 69 }],
                filtered_content: [{ id: 69 }],
            };

            let item = {
                label: "photothermic",
                updating: true,
            };

            KanbanCtrl.createItemInPlace(item, column);
            expect(column.content).toStrictEqual([
                { id: 97 },
                { id: 69 },
                {
                    label: "photothermic",
                    updating: true,
                    is_collapsed: true,
                },
            ]);
            expect(column.filtered_content).toStrictEqual([
                { id: 69 },
                {
                    label: "photothermic",
                    updating: true,
                    is_collapsed: true,
                },
            ]);
            expect(column.filtered_content).not.toBe(column.content);
            $scope.$apply();

            expect(column.content[2].updating).toBeFalsy();
            expect(KanbanItemRestService.createItem).toHaveBeenCalledWith(
                kanban.id,
                column.id,
                "photothermic",
            );
        });
    });

    describe("createItemInPlaceInBacklog()", () => {
        it(`Given a label, when I create a new kanban item in the backlog,
            then it will be created using KanbanItemRestService
            and will be appended to the backlog`, () => {
            jest.spyOn(SharedPropertiesService, "doesUserPrefersCompactCards").mockReturnValue(
                true,
            );
            jest.spyOn(KanbanItemRestService, "createItemInBacklog").mockReturnValue(
                $q.when({ id: 11, label: "unbeautifully" }),
            );
            KanbanCtrl.backlog.content = [{ id: 91 }, { id: 85 }];
            KanbanCtrl.backlog.filtered_content = [{ id: 91 }];

            const item = {
                label: "unbeautifully",
                updating: true,
            };

            KanbanCtrl.createItemInPlaceInBacklog(item);
            expect(KanbanCtrl.backlog.content).toStrictEqual([
                { id: 91 },
                { id: 85 },
                {
                    label: "unbeautifully",
                    updating: true,
                    is_collapsed: true,
                },
            ]);
            expect(KanbanCtrl.backlog.filtered_content).toStrictEqual([
                { id: 91 },
                {
                    label: "unbeautifully",
                    updating: true,
                    is_collapsed: true,
                },
            ]);
            expect(KanbanCtrl.backlog.filtered_content).not.toBe(KanbanCtrl.backlog.content);
            $scope.$apply();

            expect(KanbanCtrl.backlog.content[2].updating).toBeFalsy();
            expect(KanbanItemRestService.createItemInBacklog).toHaveBeenCalledWith(
                kanban.id,
                "unbeautifully",
            );
        });
    });

    describe("filterCards() -", function () {
        describe("Given that the backlog column", function () {
            it("was open, when I filter the kanban, then the backlog will be filtered", function () {
                KanbanCtrl.backlog.is_open = true;

                KanbanCtrl.filterCards();

                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(KanbanCtrl.backlog);
            });

            it("was closed, when I filter the kanban, then the backlog won't be filtered", function () {
                KanbanCtrl.backlog.is_open = false;

                KanbanCtrl.filterCards();

                expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
            });
        });

        describe("Given that the archive column", function () {
            it("was open, when I filter the kanban, then the archive will be filtered", function () {
                KanbanCtrl.archive.is_open = true;

                KanbanCtrl.filterCards();

                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(KanbanCtrl.archive);
            });

            it("was closed, when I filter the kanban, then the archive won't be filtered", function () {
                KanbanCtrl.archive.is_open = false;

                KanbanCtrl.filterCards();

                expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
            });
        });

        describe("Given a kanban column", function () {
            var column;

            beforeEach(function () {
                emptyArray(kanban.columns);
                column = {
                    id: 8,
                    content: [{ id: 49 }, { id: 27 }],
                    filtered_content: [{ id: 49 }, { id: 27 }],
                };
                kanban.columns[0] = column;
            });

            it("that was open, when I filter the kanban, then the column will be filtered", function () {
                column.is_open = true;

                KanbanCtrl.filterCards();

                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(column);
            });

            it("that was closed, when I filter the kanban, then the column won't be filtered", function () {
                column.is_open = false;

                KanbanCtrl.filterCards();

                expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
            });
        });
    });

    describe("showEditModal() -", function () {
        var fake_event;
        beforeEach(function () {
            jest.spyOn(NewTuleapArtifactModalService, "showEdition").mockImplementation(() => {});
            SharedPropertiesService.getUserId.mockReturnValue(102);
            fake_event = {
                which: 1,
                preventDefault: jest.fn(),
            };
        });

        it("Given a left mouse click event, when I show the edition modal, then the default event will be prevented", function () {
            KanbanCtrl.showEditModal(fake_event, {
                id: 55,
                color: "infaust",
            });

            expect(fake_event.preventDefault).toHaveBeenCalled();
        });

        it("Given an item, when I show the edition modal, then the Tuleap Artifact Modal service will be called", function () {
            KanbanCtrl.showEditModal(fake_event, {
                id: 4288,
                color: "Indianhood",
            });

            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(
                102,
                56,
                4288,
                expect.any(Function),
            );
        });

        describe("callback -", function () {
            var fake_updated_item;
            var get_request;

            beforeEach(function () {
                NewTuleapArtifactModalService.showEdition.mockImplementation(
                    function (c, a, b, callback) {
                        callback();
                    },
                );
                get_request = $q.defer();
                jest.spyOn(KanbanItemRestService, "getItem").mockReturnValue(get_request.promise);
                jest.spyOn(KanbanCtrl, "moveItemAtTheEndWithoutItemUpdate").mockImplementation(
                    () => {},
                );

                var archive = {
                    id: "archive",
                };
                var column = {
                    id: 252,
                };
                ColumnCollectionService.getColumn.mockImplementation(function (column_id) {
                    if (column_id === "archive") {
                        return archive;
                    }

                    return column;
                });

                fake_updated_item = {
                    id: 108,
                    color: "relapse",
                    card_fields: [
                        {
                            field_id: 27,
                            type: "string",
                            label: "title",
                            value: "omnigenous",
                        },
                    ],
                    in_column: "archive",
                    label: "omnigenous",
                };
            });

            it("Given an item and given I changed its column during edition, when the new artifact modal calls its callback, then the kanban-item service will be called, the item will be refreshed with the new values and it will be moved at the end of its new column", function () {
                KanbanCtrl.showEditModal(fake_event, {
                    id: 108,
                    color: "nainsel",
                    in_column: 252,
                    timeinfo: {},
                });
                get_request.resolve(fake_updated_item);
                $scope.$apply();

                // It should be called with the object returned by KanbanItemRestService (the one with color: 'nainsel' )
                // but jasmine (at least in version 1.3) seems to only register the object ref and not
                // make a deep copy of it, so when we update the object later with _.extend, it biases the test...
                // see https://github.com/jasmine/jasmine/issues/872
                // I'd rather have an imprecise test than a misleading one, so I used jasmine.any(Object)
                expect(KanbanCtrl.moveItemAtTheEndWithoutItemUpdate).toHaveBeenCalledWith(
                    expect.any(Object),
                    "archive",
                );
            });

            it("Given an item and given that I did not change its column during edition, when the new artifact modal calls its callback, then the item will not be moved at the end of its new column", function () {
                KanbanCtrl.showEditModal(fake_event, {
                    id: 108,
                    color: "unpracticably",
                    in_column: "archive",
                    timeinfo: {},
                });
                get_request.resolve(fake_updated_item);
                $scope.$apply();

                expect(KanbanCtrl.moveItemAtTheEndWithoutItemUpdate).not.toHaveBeenCalled();
            });
        });
    });

    describe("openAddArtifactModal() -", () => {
        var fake_event;
        beforeEach(function () {
            jest.spyOn(NewTuleapArtifactModalService, "showCreation").mockImplementation(() => {});
            fake_event = {
                which: 1,
                preventDefault: jest.fn(),
            };
        });

        it("Should open the artifact creation modal", () => {
            KanbanCtrl.openAddArtifactModal(fake_event);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                user_id,
                56,
                null,
                expect.any(Function),
                [],
            );
        });
        describe("callback -", function () {
            let created_artifact;
            let get_request;
            let archive;
            let collapsed_column;
            let compared_to;

            beforeEach(function () {
                NewTuleapArtifactModalService.showCreation.mockImplementation(
                    function (user_id, a, b, callback) {
                        callback();
                    },
                );
                get_request = $q.defer();
                jest.spyOn(KanbanItemRestService, "getItem").mockReturnValue(get_request.promise);
                jest.spyOn(SharedPropertiesService, "doesUserPrefersCompactCards").mockReturnValue(
                    true,
                );
                jest.spyOn(SharedPropertiesService, "isNodeServerConnected").mockReturnValue(true);
                jest.spyOn(KanbanColumnService, "addItem").mockImplementation(() => {});
                jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(() => {});

                archive = {
                    id: "archive",
                    is_open: true,
                    filtered_content: [{ id: 3 }, { id: 4 }],
                };
                collapsed_column = {
                    id: 252,
                    is_open: false,
                    filtered_content: [{ id: 1 }, { id: 2 }, { id: 10 }],
                };
                compared_to = {
                    direction: "before",
                    item_id: 44,
                };
                ColumnCollectionService.getColumn.mockImplementation(function (column_id) {
                    if (column_id === "archive") {
                        return archive;
                    }

                    return collapsed_column;
                });

                DroppedService.getComparedToBeLastItemOfColumn.mockReturnValue(compared_to);

                created_artifact = {
                    id: 108,
                    color: "relapse",
                    card_fields: [
                        {
                            field_id: 27,
                            type: "string",
                            label: "title",
                            value: "omnigenous",
                        },
                    ],
                    in_column: "archive",
                    label: "omnigenous",
                };
            });
            it("does not nothing in the kanban if there is no artifact", () => {
                KanbanCtrl.openAddArtifactModal(fake_event);
                get_request.resolve(null);
                $scope.$apply();
                expect(SharedPropertiesService.doesUserPrefersCompactCards).not.toHaveBeenCalled();
                expect(ColumnCollectionService.getColumn).not.toHaveBeenCalled();
                expect(DroppedService.getComparedToBeLastItemOfColumn).not.toHaveBeenCalled();
                expect(SharedPropertiesService.isNodeServerConnected).not.toHaveBeenCalled();
                expect(KanbanColumnService.addItem).not.toHaveBeenCalled();
                expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
            });

            it("does not use the KanbanColumnService when the node server (realtime) is up", () => {
                jest.spyOn(SharedPropertiesService, "isMercureServerConnected").mockReturnValue(
                    false,
                );
                jest.spyOn(SharedPropertiesService, "isNodeServerConnected").mockReturnValue(true);

                KanbanCtrl.openAddArtifactModal(fake_event);
                get_request.resolve(created_artifact);

                $scope.$apply();
                expect(SharedPropertiesService.doesUserPrefersCompactCards).toHaveBeenCalled();
                expect(ColumnCollectionService.getColumn).toHaveBeenCalledWith("archive");
                expect(DroppedService.getComparedToBeLastItemOfColumn).toHaveBeenCalledWith(
                    archive,
                );
                expect(KanbanColumnService.addItem).not.toHaveBeenCalled();
                expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
            });

            it("does not use the KanbanColumnService when the mercure server (realtime) is up", () => {
                jest.spyOn(SharedPropertiesService, "isNodeServerConnected").mockReturnValue(false);
                jest.spyOn(SharedPropertiesService, "isMercureServerConnected").mockReturnValue(
                    true,
                );

                KanbanCtrl.openAddArtifactModal(fake_event);
                get_request.resolve(created_artifact);

                $scope.$apply();
                expect(SharedPropertiesService.doesUserPrefersCompactCards).toHaveBeenCalled();
                expect(ColumnCollectionService.getColumn).toHaveBeenCalledWith("archive");
                expect(DroppedService.getComparedToBeLastItemOfColumn).toHaveBeenCalledWith(
                    archive,
                );
                expect(KanbanColumnService.addItem).not.toHaveBeenCalled();
                expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
            });

            it("uses the KanbanColumnService to add the created item when the node server & the mercure server (realtime) are down", () => {
                jest.spyOn(SharedPropertiesService, "isNodeServerConnected").mockReturnValue(false);
                jest.spyOn(SharedPropertiesService, "isMercureServerConnected").mockReturnValue(
                    false,
                );

                KanbanCtrl.openAddArtifactModal(fake_event);
                get_request.resolve(created_artifact);

                $scope.$apply();
                expect(SharedPropertiesService.doesUserPrefersCompactCards).toHaveBeenCalled();
                expect(ColumnCollectionService.getColumn).toHaveBeenCalledWith("archive");
                expect(DroppedService.getComparedToBeLastItemOfColumn).toHaveBeenCalledWith(
                    archive,
                );
                expect(KanbanColumnService.addItem).toHaveBeenCalledWith(
                    created_artifact,
                    archive,
                    compared_to,
                );

                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(archive);
                expect(archive.filtered_content).toHaveLength(2);
            });

            it("empty the filtered column when the column is closed", () => {
                created_artifact.in_column = 252;
                jest.spyOn(SharedPropertiesService, "isNodeServerConnected").mockReturnValue(false);

                expect(collapsed_column.filtered_content).toHaveLength(3);
                KanbanCtrl.openAddArtifactModal(fake_event);
                get_request.resolve(created_artifact);

                $scope.$apply();
                expect(SharedPropertiesService.doesUserPrefersCompactCards).toHaveBeenCalled();
                expect(ColumnCollectionService.getColumn).toHaveBeenCalledWith(252);
                expect(DroppedService.getComparedToBeLastItemOfColumn).toHaveBeenCalledWith(
                    collapsed_column,
                );
                expect(KanbanColumnService.addItem).toHaveBeenCalledWith(
                    created_artifact,
                    collapsed_column,
                    compared_to,
                );

                expect(KanbanColumnService.filterItems).toHaveBeenCalledWith(collapsed_column);
                expect(collapsed_column.filtered_content).toHaveLength(0);
            });
        });
    });

    describe("moveItemAtTheEndWithoutItemUpdate() -", function () {
        it(`Given a kanban item in a column and another kanban column,
            when I move the item to the column,
            then the item will be marked as updating,
            will be removed from the previous column's content,
            will be appended to the given column's content.`, () => {
            const move_request = $q.defer();
            DroppedService.moveToColumn.mockReturnValue(move_request.promise);
            const item = {
                id: 19,
                updating: false,
                in_column: 3,
            };
            const source_column = {
                id: 3,
            };
            const destination_column = {
                id: 6,
            };
            const compared_to = {
                direction: "before",
                item_id: 19,
            };
            ColumnCollectionService.getColumn.mockImplementation(function (column_id) {
                if (column_id === 3) {
                    return source_column;
                }

                return destination_column;
            });
            DroppedService.getComparedToBeLastItemOfColumn.mockReturnValue(compared_to);

            KanbanCtrl.moveItemAtTheEndWithoutItemUpdate(
                item,
                destination_column.id,
                item.in_column,
            );

            expect(KanbanColumnService.moveItem).toHaveBeenCalledWith(
                item,
                source_column,
                destination_column,
                compared_to,
            );
        });
    });

    describe("moveKanbanItemToTop() -", function () {
        it("Given an item, when I move it to the top, then it will be moved to the top of its column", function () {
            var item = {
                id: 39,
                in_column: 9,
            };

            var column = {
                id: 95,
            };
            ColumnCollectionService.getColumn.mockReturnValue(column);
            var compared_to = {
                direction: "before",
                item_id: 44,
            };
            DroppedService.getComparedToBeFirstItemOfColumn.mockReturnValue(compared_to);

            KanbanCtrl.moveKanbanItemToTop(item);

            expect(KanbanColumnService.moveItem).toHaveBeenCalledWith(
                item,
                column,
                column,
                compared_to,
            );
            expect(DroppedService.reorderColumn).toHaveBeenCalledWith(
                kanban.id,
                column.id,
                item.id,
                compared_to,
            );
        });
    });

    describe("moveKanbanItemToBottom() -", function () {
        it("Given an item, when I move it to the bottom, then it will be moved to the bottom of its column", function () {
            var item = {
                id: 74,
                in_column: "archive",
            };

            var archive = {
                id: "archive",
            };
            ColumnCollectionService.getColumn.mockReturnValue(archive);
            var compared_to = {
                direction: "after",
                item_id: 10,
            };
            DroppedService.getComparedToBeLastItemOfColumn.mockReturnValue(compared_to);

            KanbanCtrl.moveKanbanItemToBottom(item);

            expect(KanbanColumnService.moveItem).toHaveBeenCalledWith(
                item,
                archive,
                archive,
                compared_to,
            );
            expect(DroppedService.reorderColumn).toHaveBeenCalledWith(
                kanban.id,
                "archive",
                item.id,
                compared_to,
            );
        });
    });

    describe("saveCardsViewMode() -", () => {
        beforeEach(() => {
            kanban.id = 33;
            jest.spyOn(SharedPropertiesService, "setUserPrefersCompactCards");
            jest.spyOn(UserPreferencesService, "setPreference").mockImplementation(() => {});
            jest.spyOn(KanbanCtrl, "reflowKustomScrollBars").mockImplementation(() => {});
            kanban.backlog.content = [{ is_collapsed: false }, { is_collapsed: true }];
            kanban.archive.content = [{ is_collapsed: true }, { is_collapsed: false }];
            kanban.columns[0].content = [{ is_collapsed: false }];
            kanban.columns[1].content = [{ is_collapsed: false }, { is_collapsed: true }];
        });

        it("Given kanban items that were in both view modes, when I change the cards' view mode to 'compact', then it is saved in my user preferences and each kanban item is forced to 'compact' mode", () => {
            KanbanCtrl.user_prefers_collapsed_cards = true;
            KanbanCtrl.saveCardsViewMode();

            expect(SharedPropertiesService.setUserPrefersCompactCards).toHaveBeenCalledWith(true);
            expect(UserPreferencesService.setPreference).toHaveBeenCalledWith(
                757,
                "agiledashboard_kanban_item_view_mode_33",
                "compact-view",
            );
            expect(KanbanCtrl.reflowKustomScrollBars).toHaveBeenCalled();

            var all_columns = [kanban.archive, kanban.backlog, ...kanban.columns];
            var all_kanban_items = all_columns.reduce(
                (all_items, column) => all_items.concat(column.content),
                [],
            );
            all_kanban_items.forEach((item) => {
                expect(item.is_collapsed).toBe(true);
            });
        });

        it("Given kanban items that were in both view modes, when I change the cards' view mode to 'detailed', then it is saved in my user preferences and each kanban item is forced to 'detailed' mode", () => {
            KanbanCtrl.user_prefers_collapsed_cards = false;
            KanbanCtrl.saveCardsViewMode();

            expect(SharedPropertiesService.setUserPrefersCompactCards).toHaveBeenCalledWith(false);
            expect(UserPreferencesService.setPreference).toHaveBeenCalledWith(
                757,
                "agiledashboard_kanban_item_view_mode_33",
                "detailed-view",
            );
            expect(KanbanCtrl.reflowKustomScrollBars).toHaveBeenCalled();
            var all_columns = [kanban.archive, kanban.backlog, ...kanban.columns];
            var all_kanban_items = all_columns.reduce(
                (all_items, column) => all_items.concat(column.content),
                [],
            );
            all_kanban_items.forEach((item) => {
                expect(item.is_collapsed).toBe(false);
            });
        });
    });
});
