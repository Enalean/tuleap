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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { html } from "lit/html.js";
import { selectOrThrow } from "@tuleap/dom";
import { buildSelectedValueBadgeElement } from "./selected-value-badge-template";
import type { RenderedItem } from "../../type";

describe("selected-value-badge-template", () => {
    let item: RenderedItem;

    beforeEach(() => {
        const element = document.implementation.createHTMLDocument().createElement("span");
        item = {
            id: "link-selector-item-value-103",
            is_disabled: false,
            is_selected: true,
            element,
            value: "Value 103",
            group_id: "matchingvalues",
            template: html`Value <b>103</b>`,
        };
    });

    it("Given a Rendered item, then it should render it in a badge", () => {
        const badge = buildSelectedValueBadgeElement(item, () => {
            // Do nothing
        });

        expect(badge.dataset.itemId).toBe(item.id);
        expect(badge.innerHTML).toContain("Value <b>103</b>");
    });

    it("should contain a button which triggers the remove_value_from_selection callback when it is clicked", () => {
        const remove_value_from_selection = vi.fn();
        const badge = buildSelectedValueBadgeElement(item, remove_value_from_selection);

        selectOrThrow(badge, "[data-test=remove-value-button]").dispatchEvent(
            new Event("pointerup")
        );

        expect(remove_value_from_selection).toHaveBeenCalledOnce();
    });
});
