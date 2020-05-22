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

import * as mutations from "./error-mutations.js";

describe("Store mutations", () => {
    describe("resetErrors()", () => {
        it("resets all errors", () => {
            const state = {
                has_folder_permission_error: true,
                has_folder_loading_error: true,
                folder_loading_error: "Not found",
                has_document_permission_error: true,
                has_document_loading_error: true,
                document_loading_error: "Could not load document",
                has_document_lock_error: true,
                document_lock_error: "Could not lock",
                has_global_modal_error: true,
                global_modal_error_message: "400 Bad Request",
            };

            mutations.resetErrors(state);

            expect(state.has_folder_permission_error).toBe(false);
            expect(state.has_folder_loading_error).toBe(false);
            expect(state.folder_loading_error).toBeNull();
            expect(state.has_document_permission_error).toBe(false);
            expect(state.has_document_loading_error).toBe(false);
            expect(state.document_loading_error).toBeNull();
            expect(state.has_document_lock_error).toBe(false);
            expect(state.document_lock_error).toBeNull();
            expect(state.has_global_modal_error).toBe(false);
            expect(state.global_modal_error_message).toBeNull();
        });
    });

    it("switchFolderPermissionError", () => {
        const state = {
            has_folder_permission_error: false,
        };

        mutations.switchFolderPermissionError(state);
        expect(state.has_folder_permission_error).toBe(true);
    });

    it("switchItemPermissionError", () => {
        const state = {
            has_document_permission_error: false,
        };

        mutations.switchItemPermissionError(state);
        expect(state.has_document_permission_error).toBe(true);
    });

    it("setFolderLoadingError", () => {
        const state = {
            has_folder_loading_error: false,
            folder_loading_error: "",
        };

        mutations.setFolderLoadingError(state, "my error message");
        expect(state.has_folder_loading_error).toBe(true);
        expect(state.folder_loading_error).toBe("my error message");
    });

    it("setItemLoadingError", () => {
        const state = {
            has_document_loading_error: false,
            document_loading_error: "",
        };

        mutations.setItemLoadingError(state, "my error message");
        expect(state.has_document_loading_error).toBe(true);
        expect(state.document_loading_error).toBe("my error message");
    });

    it("setModalError", () => {
        const state = {
            has_modal_error: false,
            modal_error: "",
        };

        mutations.setModalError(state, "my modal error message");
        expect(state.has_modal_error).toBe(true);
        expect(state.modal_error).toBe("my modal error message");
    });

    it("resetModalError", () => {
        const state = {
            has_modal_error: true,
            modal_error: "previous error",
        };

        mutations.resetModalError(state);
        expect(state.has_modal_error).toBe(false);
        expect(state.modal_error).toBe(null);
    });

    it("setLockError", () => {
        const state = {
            has_document_lock_error: false,
            document_lock_error: "",
        };

        mutations.setLockError(state, "error lock");
        expect(state.has_document_lock_error).toBe(true);
        expect(state.document_lock_error).toBe("error lock");
    });

    describe(`setGlobalModalErrorMessage`, () => {
        it(`stores the error message to be displayed in a dedicated modal`, () => {
            const state = { global_modal_error_message: "", has_global_modal_error: false };
            mutations.setGlobalModalErrorMessage(state, "500 Internal Server Error");
            expect(state.global_modal_error_message).toBe("500 Internal Server Error");
            expect(state.has_global_modal_error).toBe(true);
        });

        it(`activates error state even if the error message is empty`, () => {
            const state = { global_modal_error_message: "", has_global_modal_error: false };
            mutations.setGlobalModalErrorMessage(state, "");
            expect(state.global_modal_error_message).toBe("");
            expect(state.has_global_modal_error).toBe(true);
        });
    });
});
