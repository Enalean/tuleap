/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
import { TAG } from "./LazyAutocompleterElement";
import { createLazyAutocompleter } from "./CreateLazyAutocompleter";

describe("CreateLazyAutocompleter", () => {
    describe(`createLazyAutocompleter`, () => {
        it(`returns a new LazyAutocompleterElement`, () => {
            const doc = document.implementation.createHTMLDocument();
            const lazy_autocompleter = createLazyAutocompleter(doc);

            expect(lazy_autocompleter.tagName).toBe(TAG.toUpperCase());
            expect(lazy_autocompleter.options).toBeUndefined();
        });
    });
});
