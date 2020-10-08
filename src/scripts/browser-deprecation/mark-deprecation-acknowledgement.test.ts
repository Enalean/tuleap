/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { markAndCheckBrowserDeprecationAcknowledgement } from "./mark-deprecation-acknowledgement";

describe("mark-deprecation-acknowledgement", () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it("marks deprecation as seen after the first time", () => {
        jest.spyOn(Date, "now").mockReturnValueOnce(1);
        jest.spyOn(Date, "now").mockReturnValueOnce(2);
        expect(markAndCheckBrowserDeprecationAcknowledgement(localStorage)).toBe(false);
        expect(markAndCheckBrowserDeprecationAcknowledgement(localStorage)).toBe(true);
    });

    it("deprecation notice is considered seen only for some time", () => {
        jest.spyOn(Date, "now").mockReturnValueOnce(1);
        const a_month_in_ms = 30 * 24 * 60 * 60 * 1000;
        jest.spyOn(Date, "now").mockReturnValueOnce(a_month_in_ms);
        expect(markAndCheckBrowserDeprecationAcknowledgement(localStorage)).toBe(false);
        expect(markAndCheckBrowserDeprecationAcknowledgement(localStorage)).toBe(false);
    });
});
