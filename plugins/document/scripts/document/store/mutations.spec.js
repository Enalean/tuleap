/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import * as mutations from "./mutations.js";

describe("Store mutations", () => {
    describe("beginLoadingFolderTitle()", () => {
        it("sets loading to true", () => {
            const state = {
                is_loading_folder_title: false
            };

            mutations.beginLoadingFolderTitle(state);

            expect(state.is_loading_folder_title).toBe(true);
        });
    });

    describe("stopLoadingFolderTitle()", () => {
        it("sets loading to false", () => {
            const state = {
                is_loading_folder_title: true
            };

            mutations.stopLoadingFolderTitle(state);

            expect(state.is_loading_folder_title).toBe(false);
        });
    });

    describe("beginLoading()", () => {
        it("sets loading to true", () => {
            const state = {
                is_loading_folder: false
            };

            mutations.beginLoading(state);

            expect(state.is_loading_folder).toBe(true);
        });
    });

    describe("stopLoading()", () => {
        it("sets loading to false", () => {
            const state = {
                is_loading_folder: true
            };

            mutations.stopLoading(state);

            expect(state.is_loading_folder).toBe(false);
        });
    });

    describe("resetErrors()", () => {
        it("resets all errors", () => {
            const state = {
                has_folder_permission_error: true,
                has_folder_loading_error: true,
                folder_loading_error: "Not found"
            };

            mutations.resetErrors(state);

            expect(state.has_folder_permission_error).toBe(false);
            expect(state.has_folder_loading_error).toBe(false);
            expect(state.folder_loading_error).toBeNull();
        });
    });
});
