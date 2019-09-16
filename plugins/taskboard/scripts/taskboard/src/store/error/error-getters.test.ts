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

import { has_global_error } from "./error-getters";
import { ErrorState } from "../../type";

describe("has_global_error", () => {
    it("returns true if there is an error", () => {
        expect(
            has_global_error({ global_error_message: "500 Internal Server Error" } as ErrorState)
        ).toBe(true);
    });
    it("returns false if there is no error", () => {
        expect(has_global_error({ global_error_message: "" } as ErrorState)).toBe(false);
    });
});
