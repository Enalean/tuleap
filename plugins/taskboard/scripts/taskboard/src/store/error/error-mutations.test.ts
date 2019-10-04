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

import * as mutations from "./error-mutations";
import { ErrorState } from "./type";

describe("Error modules mutations", () => {
    describe("setGlobalErrorMessage", () => {
        it("stores the error message", () => {
            const state = { global_error_message: "", has_global_error: false } as ErrorState;
            mutations.setGlobalErrorMessage(state, "500 Internal Server Error");
            expect(state.global_error_message).toBe("500 Internal Server Error");
            expect(state.has_global_error).toBe(true);
        });

        it("activates error state even if error message is empty", () => {
            const state = { global_error_message: "", has_global_error: false } as ErrorState;
            mutations.setGlobalErrorMessage(state, "");
            expect(state.global_error_message).toBe("");
            expect(state.has_global_error).toBe(true);
        });
    });

    describe(`setModalErrorMessage`, () => {
        it(`stores the error message`, () => {
            const state = { modal_error_message: "", has_modal_error: false } as ErrorState;
            mutations.setModalErrorMessage(state, "500 Internal Server Error");
            expect(state.modal_error_message).toBe("500 Internal Server Error");
            expect(state.has_modal_error).toBe(true);
        });

        it(`activates error state even if the error message is empty`, () => {
            const state = { modal_error_message: "", has_modal_error: false } as ErrorState;
            mutations.setModalErrorMessage(state, "");
            expect(state.modal_error_message).toBe("");
            expect(state.has_modal_error).toBe(true);
        });
    });
});
