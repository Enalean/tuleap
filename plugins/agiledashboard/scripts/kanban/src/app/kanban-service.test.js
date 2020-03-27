import kanban_module from "./app.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "../../../../../../tests/jest/angular-promise-wrapper.js";

/* eslint-disable jest/expect-expect */

describe("KanbanService -", () => {
    let wrapPromise, $httpBackend, KanbanService, RestErrorService, FilterTrackerReportService;

    beforeEach(() => {
        angular.mock.module(kanban_module);

        let $rootScope;
        angular.mock.inject(function (
            _$rootScope_,
            _$httpBackend_,
            _KanbanService_,
            _RestErrorService_,
            _FilterTrackerReportService_
        ) {
            $rootScope = _$rootScope_;
            $httpBackend = _$httpBackend_;
            KanbanService = _KanbanService_;
            RestErrorService = _RestErrorService_;
            FilterTrackerReportService = _FilterTrackerReportService_;
        });

        jest.spyOn(RestErrorService, "reload").mockImplementation(() => {});
        jest.spyOn(
            FilterTrackerReportService,
            "getSelectedFilterTrackerReportId"
        ).mockImplementation(() => {});

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(() => {
        $httpBackend.verifyNoOutstandingExpectation(false); // We already trigger $digest
        $httpBackend.verifyNoOutstandingRequest(false); // We already trigger $digest
    });

    describe("getBacklogSize() -", () => {
        it(`Given a kanban id,
            then a promise will be resolved with the total number of items of the backlog`, async () => {
            const kanban_id = 40;
            const column_size = 27;
            $httpBackend.expectHEAD("/api/v1/kanban/" + kanban_id + "/backlog").respond(200, "", {
                "X-PAGINATION-SIZE": column_size,
            });

            const promise = KanbanService.getBacklogSize(kanban_id);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toEqual(column_size);
        });

        it(`Given a kanban id,
            when my kanban is filtered,
            then the filtering report will be added to the query`, async () => {
            const tracker_report_id = 37;
            FilterTrackerReportService.getSelectedFilterTrackerReportId.mockReturnValue(
                tracker_report_id
            );
            const kanban_id = 40;
            const column_size = 3;
            const encoded_query = encodeURI(JSON.stringify({ tracker_report_id }));
            $httpBackend
                .expectHEAD("/api/v1/kanban/" + kanban_id + "/backlog?query=" + encoded_query)
                .respond(200, "", {
                    "X-PAGINATION-SIZE": column_size,
                });

            const promise = KanbanService.getBacklogSize(kanban_id);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toEqual(column_size);
        });
    });

    describe("getArchiveSize() -", () => {
        it(`Given a kanban id,
            then a promise will be resolved with the total number of items of the archive`, async () => {
            const kanban_id = 7;
            const column_size = 17;
            $httpBackend.expectHEAD("/api/v1/kanban/" + kanban_id + "/archive").respond(200, "", {
                "X-PAGINATION-SIZE": column_size,
            });

            const promise = KanbanService.getArchiveSize(kanban_id);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toEqual(column_size);
        });

        it(`Given a kanban id,
            when my kanban is filtered,
            then the filtering report will be added to the query`, async () => {
            const tracker_report_id = 88;
            FilterTrackerReportService.getSelectedFilterTrackerReportId.mockReturnValue(
                tracker_report_id
            );
            const kanban_id = 99;
            const column_size = 8;
            const encoded_query = encodeURI(JSON.stringify({ tracker_report_id }));
            $httpBackend
                .expectHEAD("/api/v1/kanban/" + kanban_id + "/archive?query=" + encoded_query)
                .respond(200, "", {
                    "X-PAGINATION-SIZE": column_size,
                });

            const promise = KanbanService.getArchiveSize(kanban_id);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toEqual(column_size);
        });
    });

    describe("getColumnContentSize() -", () => {
        it(`Given a kanban id and a column id,
            then a promise will be resolved with the total number of items of the column`, async () => {
            const kanban_id = 6;
            const column_id = 68;
            const column_size = 36;
            $httpBackend
                .expectHEAD("/api/v1/kanban/" + kanban_id + "/items?column_id=" + column_id)
                .respond(200, "", {
                    "X-PAGINATION-SIZE": column_size,
                });

            const promise = KanbanService.getColumnContentSize(kanban_id, column_id);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toEqual(column_size);
        });

        it(`Given a kanban id,
            when my kanban is filtered,
            then the filtering report will be added to the query`, async () => {
            const tracker_report_id = 65;
            FilterTrackerReportService.getSelectedFilterTrackerReportId.mockReturnValue(
                tracker_report_id
            );
            const kanban_id = 99;
            const column_id = 68;
            const column_size = 49;
            const encoded_query = encodeURI(JSON.stringify({ tracker_report_id }));
            $httpBackend
                .expectHEAD(
                    "/api/v1/kanban/" +
                        kanban_id +
                        "/items?column_id=" +
                        column_id +
                        "&query=" +
                        encoded_query
                )
                .respond(200, "", {
                    "X-PAGINATION-SIZE": column_size,
                });

            const promise = KanbanService.getColumnContentSize(kanban_id, column_id);
            $httpBackend.flush();
            expect(await wrapPromise(promise)).toEqual(column_size);
        });
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
            await wrapPromise(promise);
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
            await wrapPromise(promise);
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
            await wrapPromise(promise);
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
            await wrapPromise(promise);
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
            await wrapPromise(promise);
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
            await wrapPromise(promise);
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
            await wrapPromise(promise);
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
            await wrapPromise(promise);
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
            await wrapPromise(promise);
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

    describe("updateSelectableReports() -", () => {
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
            await wrapPromise(promise);
        });
    });
});
