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
import { html } from "lit/html.js";
import { TAG } from "./selection/SelectionBadge";
import { getSelectionBadgeCallback, isBadge } from "./SelectionBadgeCallbackDefaulter";
import type { LazyboxOptions, RenderedItem } from "./type";

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
        const options = {
            selection_badge_callback: (item: RenderedItem): void => {
                if (!item) {
                    throw new Error("No item");
                }
            },
        } as LazyboxOptions;

        expect(getSelectionBadgeCallback(options)).toStrictEqual(options.selection_badge_callback);
    });

    it(`When the option selection_badge_callback is not defined
        Then getSelectionBadgeCallback() should return a default callback
        That should render a default primary outlined badge containing the item's template`, () => {
        const callback = getSelectionBadgeCallback({} as LazyboxOptions);
        const item_template_content = "An item";
        const rendered_item = {
            template: html`${item_template_content}`,
        } as RenderedItem;

        const default_badge = callback(rendered_item);

        expect(isBadge(default_badge)).toBe(true);
        expect(default_badge.outline).toBe(true);
        expect(default_badge.color).toBe("primary");
        expect(default_badge.innerHTML).toContain(item_template_content);
    });
});
