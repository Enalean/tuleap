/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { formatDateValue } from "./date-property-helper";

describe("transformDocumentPropertyForCreation", () => {
    it(`Format a date`, () => {
        const formatted_date = formatDateValue("2019-08-30T00:00:00+02:00");

        expect(formatted_date).toBe("2019-08-30");
    });
    it(`Returns an empty string when date is null`, () => {
        const formatted_date = formatDateValue(null);

        expect(formatted_date).toBe("");
    });
});
