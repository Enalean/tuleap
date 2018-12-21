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

import * as mutations from "./transition-mutations.js";
import { create } from "../../support/factories.js";

describe("Transition mutations", () => {
    let state;

    describe("showModal()", () => {
        let transition;

        beforeEach(() => {
            state = {
                is_modal_shown: false,
                is_loading_modal: false,
                current_transition: null
            };

            transition = create("transition");

            mutations.showModal(state, transition);
        });

        it("will set the loading flag", () => expect(state.is_loading_modal).toBe(true));
        it("will set the current transition", () =>
            expect(state.current_transition).toBe(transition));
        it("will set the modal shown flag", () => expect(state.is_modal_shown).toBe(true));
    });

    describe("failModalOperation()", () => {
        let message = "Bad Request";

        beforeEach(() => {
            state = {
                is_modal_operation_failed: false,
                modal_operation_failure_message: null
            };

            mutations.failModalOperation(state, message);
        });

        it("will set the modal's failed operation flag", () =>
            expect(state.is_modal_operation_failed).toBe(true));
        it("will set the modal's error message", () =>
            expect(state.modal_operation_failure_message).toBe(message));
    });
});
