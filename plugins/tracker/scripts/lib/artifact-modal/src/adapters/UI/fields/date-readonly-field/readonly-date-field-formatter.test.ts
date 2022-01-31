/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { ReadonlyDateFieldFormatter } from "./readonly-date-field-formatter";

describe("readonly-date-field-formatter", () => {
    afterEach(() => {
        jest.clearAllMocks();
    });

    it("should format", () => {
        jest.spyOn(Intl, "DateTimeFormat").mockReturnValue({
            formatToParts: jest.fn().mockReturnValue([
                { type: "month", value: "01" },
                { type: "literal", value: "/" },
                { type: "day", value: "31" },
                { type: "literal", value: "/" },
                { type: "year", value: "2022" },
                { type: "literal", value: ", " },
                { type: "hour", value: "08" },
                { type: "literal", value: ":" },
                { type: "minute", value: "30" },
                { type: "literal", value: " " },
            ]),
        } as unknown as Intl.DateTimeFormat);

        const formatter = ReadonlyDateFieldFormatter("en-us");
        const date = formatter.format("2022-01-31T08:30:00Z");

        expect(date).toBe("2022-01-31 08:30");
    });
});
