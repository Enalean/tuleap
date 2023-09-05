/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { TAG } from "./selection/SelectionBadge";
import { getSelectionBadgeCallback, isBadge } from "./SelectionBadgeCallbackDefaulter";
import { OptionsBuilder } from "../tests/builders/OptionsBuilder";
import { LazyboxItemStub } from "../tests/stubs/LazyboxItemStub";
import { SelectionBadgeCallbackStub } from "../tests/stubs/SelectionBadgeCallbackStub";
import { TemplatingCallbackStub } from "../tests/stubs/TemplatingCallbackStub";

describe("SelectionBadgeCallbackDefaulter", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it.each([
        [false, "is not a selection badge element", (): HTMLElement => doc.createElement("div")],
        [false, "is null", (): null => null],
        [true, "is a selection badge element", (): HTMLElement => doc.createElement(TAG)],
    ])("isBadge() returns %s when the element %s", (is_badge, when, createElement) => {
        expect(isBadge(createElement())).toBe(is_badge);
    });

    it("getSelectionBadgeCallback() should return the option when it exists", () => {
        const callback = SelectionBadgeCallbackStub.build();
        const options = OptionsBuilder.withSelectionBadgeCallback(callback).build();

        expect(getSelectionBadgeCallback(options)).toBe(callback);
    });

    it(`When the option selection_badge_callback is not defined
        Then getSelectionBadgeCallback() should return a default callback
        That should render a default primary outlined badge containing the item's template`, () => {
        const templating_callback = TemplatingCallbackStub.build();
        const callback = getSelectionBadgeCallback(
            OptionsBuilder.withMultiple().withTemplatingCallback(templating_callback).build(),
        );

        const default_badge = callback(LazyboxItemStub.withDefaults({ value: { id: 12 } }));

        expect(isBadge(default_badge)).toBe(true);
        expect(default_badge.outline).toBe(true);
        expect(default_badge.color).toBe("primary");
        expect(default_badge.innerHTML).toContain("Value 12");
    });
});
