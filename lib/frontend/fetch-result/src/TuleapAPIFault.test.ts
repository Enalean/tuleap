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
import { TuleapAPIFault } from "./TuleapAPIFault";

const isTuleapAPIFault = (fault: Fault): boolean =>
    "isTuleapAPIFault" in fault && fault.isTuleapAPIFault() === true;
const isForbidden = (fault: Fault): boolean =>
    "isForbidden" in fault && fault.isForbidden() === true;
const isNotFound = (fault: Fault): boolean => "isNotFound" in fault && fault.isNotFound() === true;

describe(`TuleapAPIFault`, () => {
    it.each([
        [403, "Forbidden", true, false],
        [404, "Not Found", false, true],
        [400, "Unknown column", false, false],
    ])(
        `assigns message to Fault and sets specific methods for status codes`,
        (
            status_code: number,
            message: string,
            expected_forbidden: boolean,
            expected_not_found: boolean
        ) => {
            const fault = TuleapAPIFault.fromCodeAndMessage(status_code, message);
            expect(isFault(fault)).toBe(true);
            expect(isTuleapAPIFault(fault)).toBe(true);
            expect(isForbidden(fault)).toBe(expected_forbidden);
            expect(isNotFound(fault)).toBe(expected_not_found);
        }
    );
});
