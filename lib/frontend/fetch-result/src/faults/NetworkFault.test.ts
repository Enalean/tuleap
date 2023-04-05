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
import { NetworkFault } from "./NetworkFault";

const isNetworkFault = (fault: Fault): boolean =>
    "isNetworkFault" in fault && fault.isNetworkFault() === true;

describe(`NetworkFault`, () => {
    it.each([[new Error("Network disconnected")], [null]])(
        `coerce argument to Fault`,
        (error: unknown) => {
            const fault = NetworkFault.fromError(error);
            expect(isFault(fault)).toBe(true);
            expect(isNetworkFault(fault)).toBe(true);
        }
    );
});
