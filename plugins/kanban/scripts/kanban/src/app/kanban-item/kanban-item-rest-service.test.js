/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe("KanbanItemRestService -", function () {
    let wrapPromise, $q, KanbanItemRestService, RestErrorService;
    beforeEach(function () {
        angular.mock.module(kanban_module, function ($provide) {
            $provide.decorator("SharedPropertiesService", function ($delegate) {
                jest.spyOn($delegate, "getUUID").mockReturnValue(1312);
                return $delegate;
            });
        });

        let $rootScope;
        angular.mock.inject(function (
            _$rootScope_,
            _$q_,
            _KanbanItemRestService_,
            _RestErrorService_
        ) {
            $rootScope = _$rootScope_;
            $q = _$q_;
            KanbanItemRestService = _KanbanItemRestService_;
            RestErrorService = _RestErrorService_;
        });

        jest.spyOn(RestErrorService, "reload").mockImplementation(() => {});
        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            })
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
            })
        );
    }

    const expected_headers = {
        "content-type": "application/json",
        "X-Client-UUID": 1312,
    };

    describe(`createItem`, () => {
        it(`will call POST on kanban items to create an item in a given column
            and will return the newly created item`, async () => {
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            const item_representation = { id: 987, label: "prebenediction" };
            mockFetchSuccess(tlpPost, { return_json: item_representation });

            const promise = KanbanItemRestService.createItem(17, 64, "prebenediction");

            const new_item = await wrapPromise(promise);
            expect(new_item).toEqual(item_representation);
            expect(tlpPost).toHaveBeenCalledWith("/api/v1/kanban_items", {
                headers: expected_headers,
                body: JSON.stringify({
                    label: "prebenediction",
                    kanban_id: 17,
                    column_id: 64,
                }),
            });
        });
    });

    describe(`createItemInBacklog`, () => {
        it(`will call POST on kanban items to create an item in the backlog column
            and will return the newly created item`, async () => {
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            const item_representation = { id: 987, label: "prebenediction" };
            mockFetchSuccess(tlpPost, { return_json: item_representation });

            const promise = KanbanItemRestService.createItemInBacklog(17, "prebenediction");

            const new_item = await wrapPromise(promise);
            expect(new_item).toEqual(item_representation);
            expect(tlpPost).toHaveBeenCalledWith("/api/v1/kanban_items", {
                headers: expected_headers,
                body: JSON.stringify({
                    label: "prebenediction",
                    kanban_id: 17,
                }),
            });
        });
    });

    describe("getItem", () => {
        it(`will call GET on the kanban item and will return it`, async () => {
            const kanban_item = {
                id: 410,
                item_name: "paterfamiliarly",
                label: "Disaccustomed",
            };
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, { return_json: kanban_item });

            const promise = KanbanItemRestService.getItem(410);

            const response = await wrapPromise(promise);
            expect(response).toEqual(kanban_item);
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/kanban_items/410");
        });

        it(`When there is an error with my request,
            then the error will be handled by RestErrorService
            and a rejected promise will be returned`, () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchError(tlpGet, { status: 404, error_json: { error: { message: "Error" } } });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = KanbanItemRestService.getItem(410).catch(() => {
                // eslint-disable-next-line jest/no-conditional-expect
                expect(RestErrorService.reload).toHaveBeenCalled();
            });
            return wrapPromise(promise);
        });
    });
});
