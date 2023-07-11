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

import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";
import { SESSION_STORAGE_KEY } from "../session";

const noop = () => {
    // Do nothing
};

describe("BacklogItemCollectionService", () => {
    let $q, wrapPromise, BacklogItemCollectionService, BacklogItemService, $window;

    beforeEach(() => {
        angular.mock.module(planning_module, function ($provide) {
            $provide.decorator("$window", function () {
                $window = {
                    sessionStorage: { setItem: noop },
                    location: { reload: noop },
                };
                return $window;
            });
        });

        let $rootScope;
        angular.mock.inject(function (
            _$q_,
            _$rootScope_,
            _BacklogItemCollectionService_,
            _BacklogItemService_
        ) {
            $q = _$q_;
            $rootScope = _$rootScope_;
            BacklogItemCollectionService = _BacklogItemCollectionService_;
            BacklogItemService = _BacklogItemService_;
        });
        jest.spyOn(BacklogItemService, "getBacklogItem").mockImplementation(() => {});

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    describe("refreshBacklogItem()", () => {
        describe(`Given a backlog item's id
                and given that this item existed in the item collection`, () => {
            let initial_item;

            const BACKLOG_ITEM_ID = 7088;
            beforeEach(() => {
                initial_item = {
                    id: BACKLOG_ITEM_ID,
                    background_color_name: "",
                    card_fields: [],
                    children: {
                        data: [],
                        collapsed: true,
                        loaded: true,
                    },
                    has_children: false,
                    initial_effort: 8,
                    remaining_effort: 7,
                    label: "hexapod",
                    status: "Review",
                    updating: false,
                };

                BacklogItemCollectionService.items = {
                    7088: initial_item,
                };
            });

            it(`when I refresh it,
                then a promise will be resolved
                and the item will be fetched from the server
                and updated in the item collection`, async () => {
                const updated_item = {
                    backlog_item: {
                        id: BACKLOG_ITEM_ID,
                        background_color_name: "glossopalatine_sophic",
                        card_fields: [
                            {
                                field_id: 35,
                                label: "Remaining Story Points",
                                type: "float",
                                value: 1.5,
                            },
                        ],
                        has_children: true,
                        initial_effort: 6,
                        remaining_effort: 3,
                        label: "unspeedy",
                        status: "Closed",
                        parent: {
                            id: 504,
                            label: "pretangible",
                        },
                    },
                };

                BacklogItemService.getBacklogItem.mockReturnValue($q.when(updated_item));

                const promise = BacklogItemCollectionService.refreshBacklogItem(BACKLOG_ITEM_ID);

                expect(BacklogItemCollectionService.items[BACKLOG_ITEM_ID].updating).toBeTruthy();

                await wrapPromise(promise);
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(BACKLOG_ITEM_ID);
                expect(BacklogItemCollectionService.items[BACKLOG_ITEM_ID]).toStrictEqual({
                    id: BACKLOG_ITEM_ID,
                    background_color_name: "glossopalatine_sophic",
                    card_fields: [
                        {
                            field_id: 35,
                            label: "Remaining Story Points",
                            type: "float",
                            value: 1.5,
                        },
                    ],
                    children: {
                        data: [],
                        collapsed: true,
                        loaded: true,
                    },
                    has_children: true,
                    initial_effort: 6,
                    remaining_effort: 3,
                    label: "unspeedy",
                    status: "Closed",
                    parent: {
                        id: 504,
                        label: "pretangible",
                    },
                    updating: false,
                    updated: true,
                });
            });

            it(`when the artifact links field of the backlog item was changed,
                it will store a feedback message in the session storage
                and will reload the page`, () => {
                const reload = jest.spyOn($window.location, "reload");
                const setItem = jest.spyOn($window.sessionStorage, "setItem");

                BacklogItemCollectionService.refreshBacklogItem(BACKLOG_ITEM_ID, {
                    did_artifact_links_change: true,
                });

                expect(setItem).toHaveBeenCalledWith(SESSION_STORAGE_KEY, expect.any(String));
                expect(reload).toHaveBeenCalled();
                expect(BacklogItemService.getBacklogItem).not.toHaveBeenCalled();
            });
        });
    });
});
