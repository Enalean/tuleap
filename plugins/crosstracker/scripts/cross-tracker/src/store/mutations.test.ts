/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import * as mutations from "./mutations";
import type { State } from "../type";

describe("mutations", () => {
    describe("setSuccessMessage()", () => {
        it("Given a success message, then the success message will be set and the error message will be hidden", () => {
            const state = {
                error_message: "impeccant",
                success_message: null,
            } as State;

            mutations.setSuccessMessage(state, "Great success");

            expect(state.error_message).toBeNull();
            expect(state.success_message).toBe("Great success");
        });
    });
});
