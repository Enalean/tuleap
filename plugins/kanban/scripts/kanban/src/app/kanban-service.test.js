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
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe("KanbanService", () => {
    let wrapPromise,
        $window,
        $q,
        KanbanService,
        RestErrorService,
        FilterTrackerReportService,
        SharedPropertiesService;

    beforeEach(() => {
        angular.mock.module(kanban_module, function ($provide) {
            $provide.decorator("SharedPropertiesService", function ($delegate) {
                jest.spyOn($delegate, "getUUID").mockReturnValue(1312);
                return $delegate;
            });
        });

        let $rootScope;
        angular.mock.inject(
            function (
                _$rootScope_,
                _$window_,
                _$q_,
                _KanbanService_,
                _RestErrorService_,
                _FilterTrackerReportService_,
                _SharedPropertiesService_,
            ) {
                $rootScope = _$rootScope_;
                $window = _$window_;
                $q = _$q_;
                KanbanService = _KanbanService_;
                RestErrorService = _RestErrorService_;
                FilterTrackerReportService = _FilterTrackerReportService_;
                SharedPropertiesService = _SharedPropertiesService_;
            },
        );

        jest.spyOn(RestErrorService, "reload").mockImplementation(() => {});
        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            }),
        );
    }

    function mockFetchError(spy_function, { status, statusText, error_json } = {}) {
        spy_function.mockReturnValue(
            $q.reject({
                response: {
                    status,
                    statusText,
                    json: () => $q.when(error_json),
                },
            }),
        );
    }

    const expected_headers = {
        "content-type": "application/json",
        "X-Client-UUID": 1312,
    };

    describe.each([
        ["getBacklog", "/api/v1/kanban/32/backlog", {}, () => KanbanService.getBacklog(32, 50, 0)],
        ["getArchive", "/api/v1/kanban/32/archive", {}, () => KanbanService.getArchive(32, 50, 0)],
        [
            "getItems",
            "/api/v1/kanban/73/items",
            { column_id: 87 },
            () => KanbanService.getItems(73, 87, 50, 0),
        ],
    ])(
        `%s will call GET on %s`,
        (function_name, expected_url, additional_params, methodUnderTest) => {
            it.each([
                [0, {}, true],
                [0, {}, false],
                [129, { query: JSON.stringify({ tracker_report_id: 129 }) }, true],
            ])(
                `will augment each kanban item and return the total number of items`,
                async (filter_report_id, query, should_item_be_collapsed) => {
                    jest.spyOn(
                        FilterTrackerReportService,
                        "getSelectedFilterTrackerReportId",
                    ).mockReturnValue(filter_report_id);
                    jest.spyOn(
                        SharedPropertiesService,
                        "doesUserPrefersCompactCards",
                    ).mockReturnValue(should_item_be_collapsed);
                    const first_item = { id: 94, item_name: "exotropia" };
                    const second_item = { id: 96, item_name: "trigeminous" };

                    const tlpGet = jest.spyOn(tlp_fetch, "get");
                    mockFetchSuccess(tlpGet, {
                        return_json: { collection: [first_item, second_item] },
                        headers: { get: () => "2" },
                    });

                    const promise = methodUnderTest();
                    const response = await wrapPromise(promise);

                    expect(response.total).toBe("2");
                    const first_kanban_item = response.results[0];
                    expect(first_kanban_item.id).toBe(94);
                    expect(first_kanban_item.is_collapsed).toBe(should_item_be_collapsed);

                    const second_kanban_item = response.results[1];
                    expect(second_kanban_item.id).toBe(96);
                    expect(second_kanban_item.is_collapsed).toBe(should_item_be_collapsed);

                    expect(tlpGet).toHaveBeenCalledWith(expected_url, {
                        params: { limit: 50, offset: 0, ...additional_params, ...query },
                    });
                },
            );
        },
    );

    describe.each([
        ["getBacklogSize", "/api/v1/kanban/40/backlog", {}, () => KanbanService.getBacklogSize(40)],
        ["getArchiveSize", "/api/v1/kanban/42/archive", {}, () => KanbanService.getArchiveSize(42)],
        [
            "getColumnContentSize",
            "/api/v1/kanban/45/items",
            { column_id: 68 },
            () => KanbanService.getColumnContentSize(45, 68),
        ],
    ])(
        "%s will call HEAD on %s",
        (function_name, expected_url, additional_params, methodUnderTest) => {
            it.each([
                [0, {}],
                [37, { query: JSON.stringify({ tracker_report_id: 37 }) }],
            ])(`will return the total number of items`, async (filter_report_id, query) => {
                const tlpHead = jest.spyOn(tlp_fetch, "head");
                mockFetchSuccess(tlpHead, { headers: { get: () => "27" } });
                jest.spyOn(
                    FilterTrackerReportService,
                    "getSelectedFilterTrackerReportId",
                ).mockReturnValue(filter_report_id);

                const promise = methodUnderTest();
                await expect(wrapPromise(promise)).resolves.toBe(27);
                expect(tlpHead).toHaveBeenCalledWith(expected_url, {
                    params: { ...additional_params, ...query },
                });
            });
        },
    );

    it.each([
        [
            "reorderBacklog",
            "/api/v1/kanban/37/backlog",
            { order: { ids: [987], direction: "after", compared_to: 234 } },
            () => KanbanService.reorderBacklog(37, 987, { direction: "after", item_id: 234 }),
        ],
        [
            "reorderArchive",
            "/api/v1/kanban/6/archive",
            { order: { ids: [987], direction: "before", compared_to: 234 } },
            () => KanbanService.reorderArchive(6, 987, { direction: "before", item_id: 234 }),
        ],
        [
            "reorderColumn",
            "/api/v1/kanban/7/items?column_id=66",
            { order: { ids: [987], direction: "after", compared_to: 234 } },
            () => KanbanService.reorderColumn(7, 66, 987, { direction: "after", item_id: 234 }),
        ],
        [
            "moveInBacklog",
            "/api/v1/kanban/9/backlog",
            {
                add: { ids: [987] },
                from_column: 88,
                order: { ids: [987], direction: "after", compared_to: 234 },
            },
            () => KanbanService.moveInBacklog(9, 987, { direction: "after", item_id: 234 }, 88),
        ],
        [
            "moveInBacklog with null compared to",
            "/api/v1/kanban/9/backlog",
            { add: { ids: [987] }, from_column: 88 },
            () => KanbanService.moveInBacklog(9, 987, null, 88),
        ],
        [
            "moveInArchive",
            "/api/v1/kanban/4/archive",
            {
                add: { ids: [987] },
                from_column: 73,
                order: { ids: [987], direction: "before", compared_to: 234 },
            },
            () => KanbanService.moveInArchive(4, 987, { direction: "before", item_id: 234 }, 73),
        ],
        [
            "moveInArchive with null compared to",
            "/api/v1/kanban/4/archive",
            { add: { ids: [987] }, from_column: 73 },
            () => KanbanService.moveInArchive(4, 987, null, 73),
        ],
        [
            "moveInColumn",
            "/api/v1/kanban/1/items?column_id=88",
            {
                add: { ids: [987] },
                from_column: 12,
                order: { ids: [987], direction: "before", compared_to: 234 },
            },
            () => KanbanService.moveInColumn(1, 88, 987, { direction: "before", item_id: 234 }, 12),
        ],
        [
            "moveInColumn with null compared to",
            "/api/v1/kanban/1/items?column_id=88",
            { add: { ids: [987] }, from_column: 12 },
            () => KanbanService.moveInColumn(1, 88, 987, null, 12),
        ],
    ])(
        `%s will call PATCH on the kanban column and will move and reorder items`,
        async (function_name, expected_url, expected_body, methodUnderTest) => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = methodUnderTest();
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith(expected_url, {
                headers: expected_headers,
                body: JSON.stringify(expected_body),
            });
        },
    );

    it.each([
        [() => KanbanService.reorderBacklog(37, 987, { direction: "after", item_id: 234 })],
        [() => KanbanService.reorderArchive(6, 987, { direction: "after", item_id: 234 })],
        [() => KanbanService.reorderColumn(7, 66, 987, { direction: "after", item_id: 234 })],
        [() => KanbanService.moveInBacklog(9, 987, { direction: "before", item_id: 234 }, 918)],
        [() => KanbanService.moveInArchive(9, 987, { direction: "before", item_id: 234 }, 918)],
        [() => KanbanService.moveInColumn(9, 66, 987, { direction: "before", item_id: 234 }, 918)],
    ])(
        `When there is an error with my request,
    then the error will be handled by RestErrorService
    and a rejected promise will be returned`,
        (methodUnderTest) => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchError(tlpPatch, {
                status: 401,
                error_json: { error: { message: "Unauthorized" } },
            });

            const promise = methodUnderTest().catch(() => {
                // eslint-disable-next-line jest/no-conditional-expect
                expect(RestErrorService.reload).toHaveBeenCalled();
            });

            return wrapPromise(promise);
        },
    );

    describe(`updateKanbanLabel`, () => {
        it(`will call PATCH on the kanban to change its label`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = KanbanService.updateKanbanLabel(8, "relicmonger");
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/kanban/8", {
                headers: expected_headers,
                body: JSON.stringify({ label: "relicmonger" }),
            });
        });
    });

    describe(`deleteKanban`, () => {
        it(`will call DELETE on the kanban`, async () => {
            const tlpDelete = jest.spyOn(tlp_fetch, "del");
            mockFetchSuccess(tlpDelete);

            const promise = KanbanService.deleteKanban(8);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpDelete).toHaveBeenCalledWith("/api/v1/kanban/8", {
                headers: expected_headers,
            });
        });
    });

    describe(`expandColumn`, () => {
        it(`will call PATCH on the kanban to expand a column`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = KanbanService.expandColumn(8, 97);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/kanban/8", {
                headers: expected_headers,
                body: JSON.stringify({ collapse_column: { column_id: 97, value: false } }),
            });
        });
    });

    describe(`collapseColumn`, () => {
        it(`will call PATCH on the kanban to collapse a column`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = KanbanService.collapseColumn(8, 97);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/kanban/8", {
                headers: expected_headers,
                body: JSON.stringify({ collapse_column: { column_id: 97, value: true } }),
            });
        });
    });

    describe(`expandBacklog`, () => {
        it(`will call PATCH on the kanban to expand its backlog column`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = KanbanService.expandBacklog(8);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/kanban/8", {
                headers: expected_headers,
                body: JSON.stringify({ collapse_backlog: false }),
            });
        });
    });

    describe(`collapseBacklog`, () => {
        it(`will call PATCH on the kanban to collapse its backlog column`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = KanbanService.collapseBacklog(8);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/kanban/8", {
                headers: expected_headers,
                body: JSON.stringify({ collapse_backlog: true }),
            });
        });
    });

    describe(`expandArchive`, () => {
        it(`will call PATCH on the kanban to expand its archive column`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = KanbanService.expandArchive(8);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/kanban/8", {
                headers: expected_headers,
                body: JSON.stringify({ collapse_archive: false }),
            });
        });
    });

    describe(`collapseArchive`, () => {
        it(`will call PATCH on the kanban to collapse its archive column`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = KanbanService.collapseArchive(8);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/kanban/8", {
                headers: expected_headers,
                body: JSON.stringify({ collapse_archive: true }),
            });
        });
    });

    describe(`addColumn`, () => {
        it(`will call POST on the kanban's columns to create a new column
            and will return the new column's representation`, async () => {
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            const column_representation = { id: 876, label: "Review", is_open: true };
            mockFetchSuccess(tlpPost, { return_json: column_representation });

            const promise = KanbanService.addColumn(8, "Review");
            const new_column = await wrapPromise(promise);
            expect(new_column).toEqual(column_representation);
            expect(tlpPost).toHaveBeenCalledWith("/api/v1/kanban/8/columns", {
                headers: expected_headers,
                body: JSON.stringify({ label: "Review" }),
            });
        });
    });

    describe(`reorderColumns`, () => {
        it(`will call PUT on the kanban's columns to reorder columns`, async () => {
            const tlpPut = jest.spyOn(tlp_fetch, "put");
            mockFetchSuccess(tlpPut);

            const promise = KanbanService.reorderColumns(8, [20, 19, 21]);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPut).toHaveBeenCalledWith("/api/v1/kanban/8/columns", {
                headers: expected_headers,
                body: JSON.stringify([20, 19, 21]),
            });
        });
    });

    describe(`removeColumn`, () => {
        it(`will call DELETE on the kanban columns`, async () => {
            const tlpDelete = jest.spyOn(tlp_fetch, "del");
            mockFetchSuccess(tlpDelete);

            const promise = KanbanService.removeColumn(8, 19);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpDelete).toHaveBeenCalledWith("/api/v1/kanban_columns/19?kanban_id=8", {
                headers: expected_headers,
            });
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
                const tlpPatch = jest.spyOn(tlp_fetch, "patch");
                mockFetchSuccess(tlpPatch);

                const promise = KanbanService.editColumn(8, {
                    id: 21,
                    label: "On going",
                    limit_input: column_wip_limit,
                });
                await expect(wrapPromise(promise)).resolves.toBeTruthy();
                expect(tlpPatch).toHaveBeenCalledWith("/api/v1/kanban_columns/21?kanban_id=8", {
                    headers: expected_headers,
                    body: JSON.stringify({ label: "On going", wip_limit: expected_wip_limit }),
                });
            },
        );
    });

    describe(`updateKanbanName`, () => {
        it(`will change the label of the kanban in SharedPropertiesService`, () => {
            const kanban = { label: "original" };
            jest.spyOn(SharedPropertiesService, "getKanban").mockReturnValue(kanban);

            KanbanService.updateKanbanName("modified");
            expect(kanban.label).toBe("modified");
        });
    });

    describe(`removeKanban`, () => {
        it(`will store a feedback message in session storage
            and will redirect to the Agile Dashboard homepage`, () => {
            delete window.location;
            window.location = { href: "localhost" };
            const kanban = { label: "Tasks" };
            jest.spyOn(SharedPropertiesService, "getKanban").mockReturnValue(kanban);

            jest.spyOn(SharedPropertiesService, "getKanbanHomepageUrl").mockReturnValue(
                "/projects/acme/kanban",
            );
            const setItem = jest.spyOn(Storage.prototype, "setItem");

            KanbanService.removeKanban();

            expect(setItem).toHaveBeenCalledWith("tuleap_feedback", expect.any(String));
            expect($window.location.href).toBe("/projects/acme/kanban");
        });
    });

    describe("updateSelectableReports", () => {
        it(`will call PUT on the kanban's tracker reports
            and update the selectable reports`, async () => {
            const tlpPut = jest.spyOn(tlp_fetch, "put");
            mockFetchSuccess(tlpPut);

            const kanban_id = 59;
            const selectable_report_ids = [61, 21];

            const promise = KanbanService.updateSelectableReports(kanban_id, selectable_report_ids);
            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPut).toHaveBeenCalledWith("/api/v1/kanban/59/tracker_reports", {
                headers: expected_headers,
                body: JSON.stringify({ tracker_report_ids: selectable_report_ids }),
            });
        });
    });
});
