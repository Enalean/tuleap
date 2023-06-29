/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";
import * as tlp_fetch from "@tuleap/tlp-fetch";

class FetchWrapperErrorMock extends Error {
    response;
    constructor(response) {
        super();
        this.response = response;
    }
}

describe("MercureService -", function () {
    let $q,
        KanbanColumnService,
        ColumnCollectionService,
        MercureService,
        KanbanItemRestService,
        SharedPropertiesService,
        KanbanService,
        $rootScope,
        wrapPromise;
    beforeEach(function () {
        angular.mock.module(kanban_module, function ($provide) {
            $provide.decorator("SharedPropertiesService", function ($delegate) {
                jest.spyOn($delegate, "getKanban").mockReturnValue({ id: 1, columns: [] });
                jest.spyOn($delegate, "doesUserPrefersCompactCards").mockReturnValue(true);
                jest.spyOn($delegate, "getUUID").mockReturnValue(1234);
                return $delegate;
            });
            $provide.decorator("KanbanItemRestService", function ($delegate) {
                jest.spyOn($delegate, "getItem").mockImplementation(() => {});

                return $delegate;
            });
        });
        angular.mock.inject(function (
            _$rootScope_,
            _$q_,
            _moment_,
            _MercureService_,
            _ColumnCollectionService_,
            _KanbanColumnService_,
            _KanbanItemRestService_,
            _KanbanService_,
            _SharedPropertiesService_
        ) {
            $rootScope = _$rootScope_;
            $q = _$q_;
            MercureService = _MercureService_;
            KanbanItemRestService = _KanbanItemRestService_;
            KanbanColumnService = _KanbanColumnService_;
            ColumnCollectionService = _ColumnCollectionService_;
            KanbanService = _KanbanService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });
    function mockMessageItemUpdate() {
        return { data: { artifact_id: 1 } };
    }

    function mockMessageItemMoved() {
        return { data: { from_column: 1, in_column: 2 } };
    }

    function mockMessageItemCreate() {
        return { data: { artifact_id: 1 } };
    }
    function mockFetchSucess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            })
        );
    }
    function mockKanbanGet() {
        return { status: 200, kanban: { name: "test", columns: [] } };
    }
    function mockKanban() {
        return { label: "test", columns: [] };
    }
    function mockColumn() {
        return { content: [{ id: 1 }, { id: 2 }, { id: 3 }] };
    }
    describe("listenKanbanItemUpdate", () => {
        it("will get an update event from mercure , the item is in the kanban, and it will update it", async () => {
            let message = mockMessageItemUpdate();
            const item = {
                id: 50,
            };
            KanbanItemRestService.getItem.mockReturnValue(wrapPromise($q.when(item)));
            jest.spyOn(ColumnCollectionService, "findItemById").mockReturnValue({ id: 1 });
            jest.spyOn(ColumnCollectionService, "getColumn").mockReturnValue(mockColumn());
            jest.spyOn(KanbanColumnService, "updateItemContent").mockImplementation(function () {});
            jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(function () {});
            await MercureService.listenKanbanItemUpdate(message);
            expect(KanbanColumnService.updateItemContent).toHaveBeenCalled();
            expect(KanbanColumnService.filterItems).toHaveBeenCalled();
        });
        it("will get an update event from mercure, the item is not in the kanban, and nothing will happen", async () => {
            let message = mockMessageItemUpdate();
            const item = {
                id: 50,
            };
            KanbanItemRestService.getItem.mockReturnValue(wrapPromise($q.when(item)));
            jest.spyOn(ColumnCollectionService, "findItemById").mockReturnValue(null);
            jest.spyOn(ColumnCollectionService, "getColumn").mockReturnValue(mockColumn());
            jest.spyOn(KanbanColumnService, "updateItemContent").mockImplementation(function () {});
            jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(function () {});
            await MercureService.listenKanbanItemUpdate(message);
            expect(KanbanColumnService.updateItemContent).not.toHaveBeenCalled();
            expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
        });

        it("will get an update event from mercure, the item cannot be found trough the api, and nothing will happen", async () => {
            let message = mockMessageItemUpdate();

            KanbanItemRestService.getItem.mockReturnValue(wrapPromise($q.when()));
            jest.spyOn(ColumnCollectionService, "findItemById").mockReturnValue({
                id: 1,
                columns: [],
            });
            jest.spyOn(ColumnCollectionService, "getColumn").mockReturnValue(mockColumn());
            jest.spyOn(KanbanColumnService, "updateItemContent").mockImplementation(function () {});
            jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(function () {});
            await MercureService.listenKanbanItemUpdate(message);
            expect(KanbanColumnService.updateItemContent).not.toHaveBeenCalled();
            expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
        });
    });
    describe("listenKanbanItemMoved", () => {
        it("will get a moved event from mercure, and will move the item from column a to column b", async () => {
            let message = mockMessageItemMoved();
            jest.spyOn(ColumnCollectionService, "getColumn").mockReturnValue(mockColumn());
            jest.spyOn(KanbanColumnService, "findItemAndReorderItemMercure").mockImplementation(
                function () {}
            );
            await MercureService.listenKanbanItemMoved(message);
            expect(KanbanColumnService.findItemAndReorderItemMercure).toHaveBeenCalled();
        });
        it("will get a moved event from mercure, and the item is in a column that is not in this kanban", async () => {
            let message = mockMessageItemMoved();
            jest.spyOn(ColumnCollectionService, "getColumn").mockReturnValue(null);
            jest.spyOn(KanbanColumnService, "findItemAndReorderItemMercure").mockImplementation(
                function () {}
            );
            await MercureService.listenKanbanItemMoved(message);
            expect(KanbanColumnService.findItemAndReorderItemMercure).not.toHaveBeenCalled();
        });
    });
    describe("listenKanbanItemCreate", () => {
        it("will get a create event from mercure, and will create the item in the kanban", async () => {
            let message = mockMessageItemCreate();
            const item = {
                id: 50,
            };
            KanbanItemRestService.getItem.mockReturnValue(wrapPromise($q.when(item)));
            jest.spyOn(ColumnCollectionService, "getColumn").mockReturnValue(mockColumn());
            jest.spyOn(ColumnCollectionService, "findItemById").mockReturnValue(null);
            jest.spyOn(KanbanColumnService, "addItem").mockImplementation(function () {});
            jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(function () {});
            await MercureService.listenKanbanItemCreate(message);
            expect(KanbanColumnService.addItem).toHaveBeenCalled();
            expect(KanbanColumnService.filterItems).toHaveBeenCalled();
        });

        it("will get a create event from mercure, and the item cannot be acessed through rest so nothing happens", async () => {
            let message = mockMessageItemCreate();
            KanbanItemRestService.getItem.mockReturnValue(wrapPromise($q.when(null)));
            jest.spyOn(ColumnCollectionService, "getColumn").mockReturnValue(mockColumn());
            jest.spyOn(ColumnCollectionService, "findItemById").mockReturnValue(null);
            jest.spyOn(KanbanColumnService, "addItem").mockImplementation(function () {});
            jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(function () {});
            await MercureService.listenKanbanItemCreate(message);
            expect(KanbanColumnService.addItem).not.toHaveBeenCalled();
            expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
        });

        it("will get a create event from mercure, and since it can find that the item has already been created, nothing happens", async () => {
            let message = mockMessageItemCreate();
            const item = {
                id: 50,
            };
            KanbanItemRestService.getItem.mockReturnValue(wrapPromise($q.when(item)));
            jest.spyOn(ColumnCollectionService, "getColumn").mockReturnValue(mockColumn());
            jest.spyOn(ColumnCollectionService, "findItemById").mockReturnValue({ id: 1 });
            jest.spyOn(KanbanColumnService, "addItem").mockImplementation(function () {});
            jest.spyOn(KanbanColumnService, "filterItems").mockImplementation(function () {});
            await MercureService.listenKanbanItemCreate(message);
            expect(KanbanColumnService.addItem).not.toHaveBeenCalled();
            expect(KanbanColumnService.filterItems).not.toHaveBeenCalled();
        });
    });
    describe("getKanban", () => {
        it("will get the kanban from the api", async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSucess(tlpGet, { return_json: mockKanbanGet() });
            const promise = MercureService.getKanban();
            const response = await wrapPromise(promise);
            expect(response).toEqual(mockKanbanGet());
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/kanban/1");
        });
    });
    describe("updateKanban", () => {
        it("will update the currently displayed kanban given a new object representation of one", async () => {
            let updateColumns = jest.fn(() => {});
            jest.spyOn(KanbanService, "updateKanbanName").mockImplementation(() => {});
            jest.spyOn(MercureService, "getToken").mockReturnValue($q.reject());
            MercureService.init(updateColumns);
            await MercureService.updateKanban(mockKanban());
            expect(KanbanService.updateKanbanName).toHaveBeenCalled();
            expect(SharedPropertiesService.getKanban).toHaveBeenCalledTimes(3);
            expect(updateColumns).toHaveBeenCalled();
        });
    });
    describe("checkResponseKanban", () => {
        it("will get a correct response  , and call updateKanban", async () => {
            let updateColumns = jest.fn(() => {});
            const kanban = mockKanban();
            jest.spyOn(MercureService, "getToken").mockReturnValue($q.reject());
            MercureService.init(updateColumns);
            jest.spyOn(KanbanService, "removeKanban").mockReturnValue($q.reject());
            await MercureService.checkResponseKanban(wrapPromise($q.when(kanban)));
            expect(KanbanService.removeKanban).not.toHaveBeenCalled();
            expect(updateColumns).toHaveBeenCalled();
        });
        it("will get a 404 response, and call removeKanban", async () => {
            const error = new FetchWrapperErrorMock({ status: 404 });
            jest.spyOn(KanbanService, "removeKanban").mockImplementation(() => {});
            const promise = wrapPromise($q.reject(error));
            await MercureService.checkResponseKanban(promise);
            expect(KanbanService.removeKanban).toHaveBeenCalled();
        });
    });
});
