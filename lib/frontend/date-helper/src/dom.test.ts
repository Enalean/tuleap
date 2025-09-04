/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
import { getTimezoneOrThrow } from "./dom";

describe(`dom`, () => {
    describe(`getTimezoneOrThrow()`, () => {
        it(`reads the data-user-timezone attribute from the given document's body and returns it`, () => {
            const doc = document.implementation.createHTMLDocument();
            const timezone = "America/New_York";
            doc.body.setAttribute("data-user-timezone", timezone);
            expect(getTimezoneOrThrow(doc)).toBe(timezone);
        });

        it(`throws an error when the document body has no data-user-timezone attribute`, () => {
            const doc = document.implementation.createHTMLDocument();
            expect(() => getTimezoneOrThrow(doc)).toThrow();
        });
    });
});
