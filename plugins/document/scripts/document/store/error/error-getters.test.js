/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import initial_state from "./module.js";
import * as getters from "./error-getters.js";

describe("error_getters", () => {
    let state;
    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("does_folder_have_any_error", () => {
        it("folder has an error if user can't write", () => {
            state.has_folder_permission_error = true;
            state.has_folder_loading_error = false;
            state.has_document_lock_error = false;
            state.has_document_permission_error = false;
            state.has_document_loading_error = false;

            const result = getters.does_folder_have_any_error(state);

            expect(result).toEqual(true);
        });

        it("folder has an error if load fail", () => {
            state.has_folder_permission_error = false;
            state.has_folder_loading_error = true;
            state.has_document_lock_error = false;
            state.has_document_permission_error = false;
            state.has_document_loading_error = false;

            const result = getters.does_folder_have_any_error(state);

            expect(result).toEqual(true);
        });

        it("folder has an error if lock action fail", () => {
            state.has_folder_permission_error = false;
            state.has_folder_loading_error = false;
            state.has_document_lock_error = true;
            state.has_document_permission_error = false;
            state.has_document_loading_error = false;

            const result = getters.does_folder_have_any_error(state);

            expect(result).toEqual(true);
        });

        it("folder has an error if document preview fail", () => {
            state.has_folder_permission_error = false;
            state.has_folder_loading_error = false;
            state.has_document_lock_error = false;
            state.has_document_permission_error = true;
            state.has_document_loading_error = false;

            const result = getters.does_folder_have_any_error(state);

            expect(result).toEqual(true);
        });

        it("folder has an error if document load fail", () => {
            state.has_folder_permission_error = false;
            state.has_folder_loading_error = false;
            state.has_document_lock_error = false;
            state.has_document_permission_error = false;
            state.has_document_loading_error = true;

            const result = getters.does_folder_have_any_error(state);

            expect(result).toEqual(true);
        });

        it("folder has no error", () => {
            state.has_folder_permission_error = false;
            state.has_folder_loading_error = false;
            state.has_document_lock_error = false;
            state.has_document_permission_error = false;
            state.has_document_loading_error = false;

            const result = getters.does_folder_have_any_error(state);

            expect(result).toEqual(false);
        });
    });

    describe("does_document_have_any_error", () => {
        it("document has an error if user can't write", () => {
            state.has_folder_loading_error = false;
            state.has_document_permission_error = true;
            state.has_document_loading_error = false;
            state.has_document_lock_error = false;

            const result = getters.does_document_have_any_error(state);

            expect(result).toEqual(true);
        });

        it("document has an error if load fail", () => {
            state.has_folder_loading_error = false;
            state.has_document_permission_error = false;
            state.has_document_loading_error = true;
            state.has_document_lock_error = false;

            const result = getters.does_document_have_any_error(state);

            expect(result).toEqual(true);
        });

        it("document has an error if lock action fail", () => {
            state.has_folder_loading_error = false;
            state.has_document_permission_error = false;
            state.has_document_loading_error = false;
            state.has_document_lock_error = true;

            const result = getters.does_document_have_any_error(state);

            expect(result).toEqual(true);
        });

        it("document has an error if the folder has an error", () => {
            state.has_folder_loading_error = true;
            state.has_document_permission_error = false;
            state.has_document_loading_error = false;
            state.has_document_lock_error = false;

            const result = getters.does_document_have_any_error(state);

            expect(result).toEqual(true);
        });

        it("document has no error", () => {
            state.has_folder_loading_error = false;
            state.has_document_permission_error = false;
            state.has_document_loading_error = false;
            state.has_document_lock_error = false;

            const result = getters.does_folder_have_any_error(state);

            expect(result).toEqual(false);
        });
    });

    describe("has_any_loading_error", () => {
        it("has an error if folder load fail", () => {
            state.has_folder_loading_error = true;
            state.has_document_loading_error = false;
            state.has_document_lock_error = false;

            const result = getters.has_any_loading_error(state);

            expect(result).toEqual(true);
        });

        it("has an error if document load fail", () => {
            state.has_folder_loading_error = false;
            state.has_document_loading_error = true;
            state.has_document_lock_error = false;

            const result = getters.has_any_loading_error(state);

            expect(result).toEqual(true);
        });

        it("has an error if lock action fail", () => {
            state.has_folder_loading_error = false;
            state.has_document_loading_error = false;
            state.has_document_lock_error = true;

            const result = getters.has_any_loading_error(state);

            expect(result).toEqual(true);
        });

        it("document has no loading error", () => {
            state.has_document_permission_error = false;
            state.has_document_loading_error = false;
            state.has_document_lock_error = false;

            const result = getters.has_any_loading_error(state);

            expect(result).toEqual(false);
        });
    });
});
