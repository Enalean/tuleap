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

import { describe, expect, it } from "vitest";
import type { Fault } from "@tuleap/fault";
import { isFault } from "@tuleap/fault";
import { GitLabAPIFault } from "./GitLabAPIFault";

const isGitLabAPIFault = (fault: Fault): boolean =>
    "isGitLabAPIFault" in fault && fault.isGitLabAPIFault() === true;
const isUnauthenticated = (fault: Fault): boolean =>
    "isUnauthenticated" in fault && fault.isUnauthenticated() === true;

describe(`GitLabAPIFault`, () => {
    it.each([
        [401, "Unauthorized", true],
        [404, "Not Found", false],
    ])(
        `assigns message to Fault and sets specific methods for status codes`,
        (status_code, message, expected_unauthenticated) => {
            const fault = GitLabAPIFault.fromStatusAndReason(status_code, message);
            expect(isFault(fault)).toBe(true);
            expect(isGitLabAPIFault(fault)).toBe(true);
            expect(isUnauthenticated(fault)).toBe(expected_unauthenticated);
        },
    );
});
