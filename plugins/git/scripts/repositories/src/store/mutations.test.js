/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import mutations from "./mutations.js";
import {
    PROJECT_KEY,
    REPOSITORIES_SORTED_BY_LAST_UPDATE,
    REPOSITORIES_SORTED_BY_PATH,
} from "../constants.js";

describe("Store mutations", () => {
    describe("setDisplayMode", () => {
        it("saves the new mode", () => {
            const state = {};

            mutations.setDisplayMode(state, REPOSITORIES_SORTED_BY_LAST_UPDATE);
            expect(state.display_mode).toBe(REPOSITORIES_SORTED_BY_LAST_UPDATE);

            mutations.setDisplayMode(state, REPOSITORIES_SORTED_BY_PATH);
            expect(state.display_mode).toBe(REPOSITORIES_SORTED_BY_PATH);
        });

        it("defaults to last update date", () => {
            const state = {};

            mutations.setDisplayMode(state, "whatever");
            expect(state.display_mode).toBe(REPOSITORIES_SORTED_BY_LAST_UPDATE);
        });
    });

    describe("pushRepositoriesForCurrentOwner", () => {
        it("Given some repositories and that the selected owner has no repositories loaded yet, then It should create an entry for him in the list, and push them in it.", () => {
            const state = {
                repositories_for_owner: {},
                selected_owner_id: 101,
            };

            mutations.pushRepositoriesForCurrentOwner(state, []);

            const has_101 = Object.prototype.hasOwnProperty.call(
                state.repositories_for_owner,
                "101"
            );
            expect(has_101).toBe(true);
        });

        it("will set the repository label as 'normalized_path' when it is a 'root' repository (without path)", () => {
            const repositories = [
                {
                    name: "archiplasm",
                    label: "archiplasm",
                    path_without_project: "",
                    path: "myproject/archiplasm.git",
                },
            ];

            const state = {
                repositories_for_owner: {},
                selected_owner_id: PROJECT_KEY,
            };

            mutations.pushRepositoriesForCurrentOwner(state, repositories);

            expect(state.repositories_for_owner[PROJECT_KEY][0].normalized_path).toEqual(
                "archiplasm"
            );
        });

        it("Given some repositories and that the selected owner has already some repositories loaded, then It should push them in his list.", () => {
            const repositories = [
                {
                    name: "boobs/straps/boobstrap4",
                    label: "boobstrap4",
                    path_without_project: "boobs/straps",
                    path: "myproject/boobs/straps/boobstrap4.git",
                },
                {
                    name: "angular.js",
                    label: "angular.js",
                    path_without_project: "u/johnpapa",
                    path: "myproject/u/johnpapa/angular.js.git",
                },
            ];

            const state = {
                repositories_for_owner: {
                    "101": [
                        {
                            label: "vuex",
                            name: "vuex",
                            path: "myproject/vuex.git",
                            path_without_project: "",
                            normalized_path: "vuex",
                        },
                    ],
                },
                selected_owner_id: 101,
            };

            mutations.pushRepositoriesForCurrentOwner(state, repositories);

            expect(state.repositories_for_owner).toEqual({
                "101": [
                    {
                        label: "vuex",
                        name: "vuex",
                        path: "myproject/vuex.git",
                        path_without_project: "",
                        normalized_path: "vuex",
                    },
                    {
                        label: "boobstrap4",
                        name: "boobs/straps/boobstrap4",
                        path: "myproject/boobs/straps/boobstrap4.git",
                        path_without_project: "boobs/straps",
                        normalized_path: "boobs/straps/boobstrap4",
                    },
                    {
                        label: "angular.js",
                        name: "angular.js",
                        path: "myproject/u/johnpapa/angular.js.git",
                        path_without_project: "u/johnpapa",
                        normalized_path: "u/johnpapa/angular.js",
                    },
                ],
            });
        });
    });
});
