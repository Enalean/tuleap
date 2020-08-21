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
import { createAngularPromiseWrapper } from "../../../../../../../tests/jest/angular-promise-wrapper.js";

describe("KanbanItemRestService -", function () {
    let wrapPromise, mockBackend, KanbanItemRestService, RestErrorService;
    beforeEach(function () {
        angular.mock.module(kanban_module);

        let $rootScope;
        angular.mock.inject(function (
            _$rootScope_,
            $httpBackend,
            _KanbanItemRestService_,
            _RestErrorService_
        ) {
            $rootScope = _$rootScope_;
            mockBackend = $httpBackend;
            KanbanItemRestService = _KanbanItemRestService_;
            RestErrorService = _RestErrorService_;
        });

        jest.spyOn(RestErrorService, "reload").mockImplementation(() => {});
        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    describe(`createItem`, () => {
        it(`will call POST on kanban items to create an item in a given column`, async () => {
            mockBackend
                .expectPOST("/api/v1/kanban_items", {
                    label: "prebenediction",
                    kanban_id: 17,
                    column_id: 64,
                })
                .respond(200);

            const promise = KanbanItemRestService.createItem(17, 64, "prebenediction");
            mockBackend.flush();

            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe(`createItemInBacklog`, () => {
        it(`will call POST on kanban items to create an item in the backlog column`, async () => {
            mockBackend
                .expectPOST("/api/v1/kanban_items", {
                    label: "prebenediction",
                    kanban_id: 17,
                })
                .respond(200);

            const promise = KanbanItemRestService.createItemInBacklog(17, "prebenediction");
            mockBackend.flush();

            expect(await wrapPromise(promise)).toBeTruthy();
        });
    });

    describe("getItem", function () {
        it(`will call GET on the kanban item and will return it`, async () => {
            mockBackend.expectGET("/api/v1/kanban_items/410").respond({
                id: 410,
                item_name: "paterfamiliarly",
                label: "Disaccustomed",
            });

            const promise = KanbanItemRestService.getItem(410);
            mockBackend.flush();

            const response = await wrapPromise(promise);
            expect(response).toEqual(
                expect.objectContaining({
                    id: 410,
                    item_name: "paterfamiliarly",
                    label: "Disaccustomed",
                })
            );
        });

        it(`When there is an error with my request,
            then the error will be handled by RestErrorService
            and a rejected promise will be returned`, () => {
            mockBackend
                .expectGET("/api/v1/kanban_items/410")
                .respond(404, { error: 404, message: "Error" });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = KanbanItemRestService.getItem(410).catch(() => {
                expect(RestErrorService.reload).toHaveBeenCalledWith(
                    expect.objectContaining({
                        data: {
                            error: 404,
                            message: "Error",
                        },
                    })
                );
            });

            mockBackend.flush();
            return wrapPromise(promise);
        });
    });
});
