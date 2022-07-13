/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { isFault } from "@tuleap/fault";
import type { Fault } from "@tuleap/fault";
import { JSONParseFault } from "./JSONParseFault";

const isJSONParseFault = (fault: Fault): boolean =>
    "isJSONParseFault" in fault && fault.isJSONParseFault() === true;

describe(`JSONParseFault`, () => {
    it.each([[new Error("Could not parse JSON")], [null]])(
        `coerce argument to Fault`,
        (error: unknown) => {
            const fault = JSONParseFault.fromError(error);
            expect(isFault(fault)).toBe(true);
            expect(isJSONParseFault(fault)).toBe(true);
        }
    );
});
