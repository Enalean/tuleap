/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
import { createAngularPromiseWrapper } from "../../../../../../tests/jest/angular-promise-wrapper.js";

describe("KanbanService -", () => {
    let wrapPromise,
        $window,
        $httpBackend,
        KanbanService,
        RestErrorService,
        FilterTrackerReportService,
        SharedPropertiesService;

    beforeEach(() => {
        angular.mock.module(kanban_module);

        let $rootScope;
        angular.mock.inject(function (
            _$rootScope_,
            _$window_,
            _$httpBackend_,
            _KanbanService_,
            _RestErrorService_,
            _FilterTrackerReportService_,
            _SharedPropertiesService_
        ) {
            $rootScope = _$rootScope_;
            $window = _$window_;
            $httpBackend = _$httpBackend_;
            KanbanService = _KanbanService_;
            RestErrorService = _RestErrorService_;
            FilterTrackerReportService = _FilterTrackerReportService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        jest.spyOn(RestErrorService, "reload").mockImplementation(() => {});
        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(() => {
        $httpBackend.verifyNoOutstandingExpectation(false); // We already trigger $digest
        $httpBackend.verifyNoOutstandingRequest(false); // We already trigger $digest
    });

    describe(`getBacklog`, () => {
        it.each([
            [0, "", true],
            [0, "", false],
            [129, "&query=%7B%22tracker_report_id%22:129%7D", true],
        ])(
            `will call GET on the kanban's backlog,
            will augment each kanban item
            and return the total number of items`,
            async (filter_report_id, query, should_item_be_collapsed) => {
                jest.spyOn(
                    FilterTrackerReportService,
                    "getSelectedFilterTrackerReportId"
                ).mockReturnValue(filter_report_id);
                jest.spyOn(SharedPropertiesService, "doesUserPrefersCompactCards").mockReturnValue(
                    should_item_be_collapsed
                );

                const first_item = { id: 94, item_name: "exotropia" };
                const second_item = { id: 96, item_name: "trigeminous" };

                $httpBackend
                    .expectGET("/api/v1/kanban/32/backlog?limit=50&offset=0" + query)
                    .respond({ collection: [first_item, second_item] }, { "X-PAGINATION-SIZE": 2 });

                const promise = KanbanService.getBacklog(32, 50, 0);
                $httpBackend.flush();
                const response = await wrapPromise(promise);

                expect(response.total).toEqual("2");
                const first_kanban_item = response.results[0];
                expect(first_kanban_item.id).toEqual(94);
                expect(first_kanban_item.is_collapsed).toBe(should_item_be_collapsed);

                const second_kanban_item = response.results[1];
                expect(second_kanban_item.id).toEqual(96);
                expect(second_kanban_item.is_collapsed).toBe(should_item_be_collapsed);
            }
        );
    });

    describe("getBacklogSize", () => {
        it.each([
            [0, ""],
            [37, "?query=%7B%22tracker_report_id%22:37%7D"],
        ])(
            `will call HEAD on the kanban's backlog
            and will return the total number of items`,
            async (filter_report_id, query) => {
                jest.spyOn(
                    FilterTrackerReportService,
                    "getSelectedFilterTrackerReportId"
                ).mockReturnValue(filter_report_id);
                $httpBackend.expectHEAD("/api/v1/kanban/40/backlog" + query).respond(200, "", {
                    "X-PAGINATION-SIZE": 27,
                });

                const promise = KanbanService.getBacklogSize(40);
                $httpBackend.flush();
                expect(await wrapPromise(promise)).toEqual(27);
            }
        );
    });

    describe(`getArchive`, () => {
        it.each([
            [0, "", true],
            [0, "", false],
            [129, "&query=%7B%22tracker_report_id%22:129%7D", true],
        ])(
            `will call GET on the kanban's archive,
            will augment each kanban item
            and return the total number of items`,
            async (filter_report_id, query, should_item_be_collapsed) => {
                jest.spyOn(
                    FilterTrackerReportService,
                    "getSelectedFilterTrackerReportId"
                ).mockReturnValue(filter_report_id);
                jest.spyOn(SharedPropertiesService, "doesUserPrefersCompactCards").mockReturnValue(
                    should_item_be_collapsed
                );

                const first_item = { id: 94, item_name: "exotropia" };
                const second_item = { id: 96, item_name: "trigeminous" };

                $httpBackend
                    .expectGET("/api/v1/kanban/32/archive?limit=50&offset=0" + query)
                    .respond({ collection: [first_item, second_item] }, { "X-PAGINATION-SIZE": 2 });

                const promise = KanbanService.getArchive(32, 50, 0);
                $httpBackend.flush();
                const response = await wrapPromise(promise);

                expect(response.total).toEqual("2");
                const first_kanban_item = response.results[0];
                expect(first_kanban_item.id).toEqual(94);
                expect(first_kanban_item.is_collapsed).toBe(should_item_be_collapsed);

                const second_kanban_item = response.results[1];
                expect(second_kanban_item.id).toEqual(96);
                expect(second_kanban_item.is_collapsed).toBe(should_item_be_collapsed);
            }
        );
    });

    describe("getArchiveSize", () => {
        it.each([
            [0, ""],
            [37, "?query=%7B%22tracker_report_id%22:37%7D"],
        ])(
            `will call HEAD on the kanban's archive
            and will return the total number of items`,
            async (filter_report_id, query) => {
                jest.spyOn(
                    FilterTrackerReportService,
                    "getSelectedFilterTrackerReportId"
                ).mockReturnValue(filter_report_id);
                $httpBackend.expectHEAD("/api/v1/kanban/7/archive" + query).respond(200, "", {
                    "X-PAGINATION-SIZE": 17,
                });

                const promise = KanbanService.getArchiveSize(7);
                $httpBackend.flush();
                expect(await wrapPromise(promise)).toEqual(17);
            }
        );
    });

    describe(`getItems`, () => {
        it.each([
            [0, "", true],
            [0, "", false],
            [129, "&query=%7B%22tracker_report_id%22:129%7D", true],
        ])(
            `will call GET on the kanban's items with a column_id,
            will augment each kanban item
            and return the total number of items`,
            async (filter_report_id, query, should_item_be_collapsed) => {
                jest.spyOn(
                    FilterTrackerReportService,
                    "getSelectedFilterTrackerReportId"
                ).mockReturnValue(filter_report_id);
                jest.spyOn(SharedPropertiesService, "doesUserPrefersCompactCards").mockReturnValue(
                    should_item_be_collapsed
                );

                const first_item = { id: 94, item_name: "exotropia" };
                const second_item = { id: 96, item_name: "trigeminous" };

                $httpBackend
                    .expectGET("/api/v1/kanban/73/items?column_id=87&limit=50&offset=0" + query)
                    .respond({ collection: [first_item, second_item] }, { "X-PAGINATION-SIZE": 2 });

                const promise = KanbanService.getItems(73, 87, 50, 0);
                $httpBackend.flush();
                const response = await wrapPromise(promise);

                expect(response.total).toEqual("2");
                const first_kanban_item = response.results[0];
                expect(first_kanban_item.id).toEqual(94);
                expect(first_kanban_item.is_collapsed).toBe(should_item_be_collapsed);

                const second_kanban_item = response.results[1];
                expect(second_kanban_item.id).toEqual(96);
                expect(second_kanban_item.is_collapsed).toBe(should_item_be_collapsed);
            }
        );
    });

    describe("getColumnContentSize", () => {
        it.each([
            [0, ""],
            [37, "&query=%7B%22tracker_report_id%22:37%7D"],
        ])(
            `will call HEAD on the kanban's items with a column_id
            and will return the total number of items`,
            async (filter_report_id, query) => {
                jest.spyOn(
                    FilterTrackerReportService,
                    "getSelectedFilterTrackerReportId"
                ).mockReturnValue(filter_report_id);
                $httpBackend
                    .expectHEAD("/api/v1/kanban/6/items?column_id=68" + query)
                    .respond(200, "", {
                        "X-PAGINATION-SIZE": 36,
                    });

                const promise = KanbanService.getColumnContentSize(6, 68);
                $httpBackend.flush();
                expect(await wrapPromise(promise)).toEqual(36);
            }
        );
    });

    describe("reorderColumn() -", function () {
        var kanban_id, column_id, kanban_item_id, compared_to;

        beforeEach(function () {
            kanban_id = 7;
            column_id = 66;
            kanban_item_id = 996;
            compared_to = {
                direction: "after",
                item_id: 268,
            };
        });

        it(`Given a kanban id, a column id, a kanban item id and a compared_to object,
            when I reorder the kanban item in the column,
            then a PATCH request will be made and a resolved promise will be returned`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/items?column_id=" + column_id, {
                    order: {
                        ids: [kanban_item_id],
                        direction: "after",
                        compared_to: 268,
                    },
                })
                .respond(200);

            const promise = KanbanService.reorderColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to
            );
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });

        it(`When there is an error with my request,
            then the error will be handled by RestErrorService
            and a rejected promise will be returned`, () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/items?column_id=" + column_id)
                .respond(401, { error: 401, message: "Unauthorized" });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = KanbanService.reorderColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to
            ).catch(() => {
                expect(RestErrorService.reload).toHaveBeenCalledWith(
                    expect.objectContaining({
                        data: {
                            error: 401,
                            message: "Unauthorized",
                        },
                    })
                );
            });

            $httpBackend.flush();
            return wrapPromise(promise);
        });
    });

    describe("reorderBacklog() -", function () {
        var kanban_id, kanban_item_id, compared_to;

        beforeEach(function () {
            kanban_id = 10;
            kanban_item_id = 194;
            compared_to = {
                direction: "before",
                item_id: 181,
            };
        });

        it(`Given a kanban_id, a kanban item id and a compared_to object,
            when I reorder the kanban item in the backlog,
            then a PATCH request will be made and a resolved promise will be returned`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/backlog", {
                    order: {
                        ids: [kanban_item_id],
                        direction: "before",
                        compared_to: 181,
                    },
                })
                .respond(200);

            const promise = KanbanService.reorderBacklog(kanban_id, kanban_item_id, compared_to);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });

        it(`When there is an error with my request,
            then the error will be handled by RestErrorService
            and a rejected promise will be returned`, () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/backlog")
                .respond(401, { error: 401, message: "Unauthorized" });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = KanbanService.reorderBacklog(
                kanban_id,
                kanban_item_id,
                compared_to
            ).catch(() => {
                expect(RestErrorService.reload).toHaveBeenCalledWith(
                    expect.objectContaining({
                        data: {
                            error: 401,
                            message: "Unauthorized",
                        },
                    })
                );
            });

            $httpBackend.flush();
            return wrapPromise(promise);
        });
    });

    describe("reorderArchive() -", function () {
        var kanban_id, kanban_item_id, compared_to;

        beforeEach(function () {
            kanban_id = 6;
            kanban_item_id = 806;
            compared_to = {
                direction: "after",
                item_id: 620,
            };
        });

        it(`Given a kanban_id, a kanban item id and a compared_to object,
            when I reorder the kanban item in the archive,
            then a PATCH request will be made
            and a resolved promise will be returned`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/archive", {
                    order: {
                        ids: [kanban_item_id],
                        direction: "after",
                        compared_to: 620,
                    },
                })
                .respond(200);

            const promise = KanbanService.reorderArchive(kanban_id, kanban_item_id, compared_to);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });

        it(`When there is an error with my request,
            then the error will be handled by RestErrorService
            and a rejected promise will be returned`, () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/archive")
                .respond(401, { error: 401, message: "Unauthorized" });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = KanbanService.reorderArchive(
                kanban_id,
                kanban_item_id,
                compared_to
            ).catch(() => {
                expect(RestErrorService.reload).toHaveBeenCalledWith(
                    expect.objectContaining({
                        data: {
                            error: 401,
                            message: "Unauthorized",
                        },
                    })
                );
            });

            $httpBackend.flush();
            return wrapPromise(promise);
        });
    });

    describe("moveInBacklog() -", function () {
        var kanban_id, kanban_item_id, compared_to, from_column;

        beforeEach(function () {
            kanban_id = 9;
            kanban_item_id = 931;
            compared_to = {
                direction: "after",
                item_id: 968,
            };
            from_column = 912;
        });

        it(`Given a kanban id, a kanban item id and a compared_to object,
            when I move the kanban item to the backlog,
            then a PATCH request will be made
            and a resolved promise will be returned`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/backlog", {
                    add: {
                        ids: [kanban_item_id],
                    },
                    order: {
                        ids: [kanban_item_id],
                        direction: "after",
                        compared_to: 968,
                    },
                    from_column: 912,
                })
                .respond(200);

            const promise = KanbanService.moveInBacklog(
                kanban_id,
                kanban_item_id,
                compared_to,
                from_column
            );
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });

        it(`Given a null compared_to,
            when I add the kanban item to an empty backlog,
            then a PATCH request will be made
            and a resolved promise will be returned`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/backlog", {
                    add: {
                        ids: [kanban_item_id],
                    },
                    from_column: 912,
                })
                .respond(200);

            const promise = KanbanService.moveInBacklog(
                kanban_id,
                kanban_item_id,
                null,
                from_column
            );
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });

        it(`When there is an error with my request,
            then the error will be handled by RestErrorService
            and a rejected promise will be returned`, () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/backlog")
                .respond(401, { error: 401, message: "Unauthorized" });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = KanbanService.moveInBacklog(
                kanban_id,
                kanban_item_id,
                compared_to,
                from_column
            ).catch(() => {
                expect(RestErrorService.reload).toHaveBeenCalledWith(
                    expect.objectContaining({
                        data: {
                            error: 401,
                            message: "Unauthorized",
                        },
                    })
                );
            });

            $httpBackend.flush();
            return wrapPromise(promise);
        });
    });

    describe("moveInArchive() -", function () {
        var kanban_id, kanban_item_id, compared_to;

        beforeEach(function () {
            kanban_id = 4;
            kanban_item_id = 598;
            compared_to = {
                direction: "before",
                item_id: 736,
            };
        });

        it(`Given a kanban id, a kanban item id and a compared_to object,
            when I move the kanban item to the archive,
            then a PATCH request will be made
            and a resolved promise will be returned`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/archive", {
                    add: {
                        ids: [kanban_item_id],
                    },
                    order: {
                        ids: [kanban_item_id],
                        direction: "before",
                        compared_to: 736,
                    },
                })
                .respond(200);

            const promise = KanbanService.moveInArchive(kanban_id, kanban_item_id, compared_to);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });

        it(`Given a null compared_to,
            when I add the kanban item to an empty archive,
            then a PATCH request will be made
            and a resolved promise will be returned`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/archive", {
                    add: {
                        ids: [kanban_item_id],
                    },
                })
                .respond(200);

            const promise = KanbanService.moveInArchive(kanban_id, kanban_item_id, null);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });

        it(`When there is an error with my request,
            then the error will be handled by RestErrorService
            and a rejected promise will be returned`, () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/archive")
                .respond(401, { error: 401, message: "Unauthorized" });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = KanbanService.moveInArchive(
                kanban_id,
                kanban_item_id,
                compared_to
            ).catch(() => {
                expect(RestErrorService.reload).toHaveBeenCalledWith(
                    expect.objectContaining({
                        data: {
                            error: 401,
                            message: "Unauthorized",
                        },
                    })
                );
            });

            $httpBackend.flush();
            return wrapPromise(promise);
        });
    });

    describe("moveInColumn() -", function () {
        var kanban_id, column_id, kanban_item_id, compared_to, from_column;

        beforeEach(function () {
            kanban_id = 1;
            column_id = 88;
            kanban_item_id = 911;
            compared_to = {
                direction: "before",
                item_id: 537,
            };
            from_column = 912;
        });

        it(`Given a kanban id, a column id, a kanban item id and a compared_to object,
            when I move the kanban item to the column,
            then a PATCH request will be made
            and a resolved promise will be returned`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/items?column_id=" + column_id, {
                    add: {
                        ids: [kanban_item_id],
                    },
                    order: {
                        ids: [kanban_item_id],
                        direction: "before",
                        compared_to: 537,
                    },
                    from_column: 912,
                })
                .respond(200);

            const promise = KanbanService.moveInColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to,
                from_column
            );
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });

        it(`Given a null compared_to,
            when I add the kanban item to an empty column,
            then a PATCH request will be made
            and a resolved promise will be returned`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/items?column_id=" + column_id, {
                    add: {
                        ids: [kanban_item_id],
                    },
                    from_column: 912,
                })
                .respond(200);

            const promise = KanbanService.moveInColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                null,
                from_column
            );
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });

        it(`When there is an error with my request,
            then the error will be handled by RestErrorService
            and a rejected promise will be returned`, () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/" + kanban_id + "/items?column_id=" + column_id)
                .respond(401, { error: 401, message: "Unauthorized" });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = KanbanService.moveInColumn(
                kanban_id,
                column_id,
                kanban_item_id,
                compared_to,
                from_column
            ).catch(() => {
                expect(RestErrorService.reload).toHaveBeenCalledWith(
                    expect.objectContaining({
                        data: {
                            error: 401,
                            message: "Unauthorized",
                        },
                    })
                );
            });

            $httpBackend.flush();
            return wrapPromise(promise);
        });
    });

    describe(`updateKanbanLabel`, () => {
        it(`will call PATCH on the kanban to change its label`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/8", {
                    label: "relicmonger",
                })
                .respond(200);

            const promise = KanbanService.updateKanbanLabel(8, "relicmonger");
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`deleteKanban`, () => {
        it(`will call DELETE on the kanban`, async () => {
            $httpBackend.expectDELETE("/api/v1/kanban/8").respond(200);

            const promise = KanbanService.deleteKanban(8);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`expandColumn`, () => {
        it(`will call PATCH on the kanban to expand a column`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/8", {
                    collapse_column: { column_id: 97, value: false },
                })
                .respond(200);

            const promise = KanbanService.expandColumn(8, 97);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`collapseColumn`, () => {
        it(`will call PATCH on the kanban to collapse a column`, async () => {
            $httpBackend
                .expectPATCH("/api/v1/kanban/8", {
                    collapse_column: { column_id: 97, value: true },
                })
                .respond(200);

            const promise = KanbanService.collapseColumn(8, 97);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`expandBacklog`, () => {
        it(`will call PATCH on the kanban to expand its backlog column`, async () => {
            $httpBackend.expectPATCH("/api/v1/kanban/8", { collapse_backlog: false }).respond(200);

            const promise = KanbanService.expandBacklog(8);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`collapseBacklog`, () => {
        it(`will call PATCH on the kanban to collapse its backlog column`, async () => {
            $httpBackend.expectPATCH("/api/v1/kanban/8", { collapse_backlog: true }).respond(200);

            const promise = KanbanService.collapseBacklog(8);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`expandArchive`, () => {
        it(`will call PATCH on the kanban to expand its archive column`, async () => {
            $httpBackend.expectPATCH("/api/v1/kanban/8", { collapse_archive: false }).respond(200);

            const promise = KanbanService.expandArchive(8);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`collapseArchive`, () => {
        it(`will call PATCH on the kanban to collapse its archive column`, async () => {
            $httpBackend.expectPATCH("/api/v1/kanban/8", { collapse_archive: true }).respond(200);

            const promise = KanbanService.collapseArchive(8);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`addColumn`, () => {
        it(`will call POST on the kanban's columns to create a new column`, async () => {
            $httpBackend.expectPOST("/api/v1/kanban/8/columns", { label: "Review" }).respond(200);

            const promise = KanbanService.addColumn(8, "Review");
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`reorderColumns`, () => {
        it(`will call PUT on the kanban's columns to reorder columns`, async () => {
            $httpBackend.expectPUT("/api/v1/kanban/8/columns", [20, 19, 21]).respond(200);

            const promise = KanbanService.reorderColumns(8, [20, 19, 21]);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`removeColumn`, () => {
        it(`will call DELETE on the kanban columns`, async () => {
            $httpBackend.expectDELETE("/api/v1/kanban_columns/19?kanban_id=8").respond(200);

            const promise = KanbanService.removeColumn(8, 19);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`editColumn`, () => {
        it.each([
            [3, 3],
            [undefined, 0],
        ])(
            `will call PATCH on the kanban columns
            and will edit the column's label and WIP limit`,
            async (column_wip_limit, expected_wip_limit) => {
                $httpBackend
                    .expectPATCH("/api/v1/kanban_columns/21?kanban_id=8", {
                        label: "On going",
                        wip_limit: expected_wip_limit,
                    })
                    .respond(200);

                const promise = KanbanService.editColumn(8, {
                    id: 21,
                    label: "On going",
                    limit_input: column_wip_limit,
                });
                $httpBackend.flush();
                expect(await wrapPromise(promise)).toBeTruthy();
            }
        );
    });

    describe(`updateKanbanName`, () => {
        it(`will change the label of the kanban in SharedPropertiesService`, () => {
            const kanban = { label: "original" };
            jest.spyOn(SharedPropertiesService, "getKanban").mockReturnValue(kanban);

            KanbanService.updateKanbanName("modified");
            expect(kanban.label).toEqual("modified");
        });
    });

    describe(`removeKanban`, () => {
        it(`will store a feedback message in session storage
            and will redirect to the Agile Dashboard homepage`, () => {
            delete window.location;
            window.location = { href: "localhost" };
            const kanban = { label: "Tasks" };
            jest.spyOn(SharedPropertiesService, "getKanban").mockReturnValue(kanban);

            jest.spyOn(SharedPropertiesService, "getProjectId").mockReturnValue(103);
            const setItem = jest.spyOn(Storage.prototype, "setItem");

            KanbanService.removeKanban();

            expect(setItem).toHaveBeenCalledWith("tuleap_feedback", expect.any(String));
            expect($window.location.href).toEqual("/plugins/agiledashboard/?group_id=103");
        });
    });

    describe("updateSelectableReports", () => {
        it(`Given a kanban id and an array of report ids,
            then a resolved promise will be returned`, async () => {
            const kanban_id = 59;
            const selectable_report_ids = [61, 21];

            $httpBackend
                .expectPUT("/api/v1/kanban/" + kanban_id + "/tracker_reports", {
                    tracker_report_ids: selectable_report_ids,
                })
                .respond(200);

            const promise = KanbanService.updateSelectableReports(kanban_id, selectable_report_ids);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });
});
