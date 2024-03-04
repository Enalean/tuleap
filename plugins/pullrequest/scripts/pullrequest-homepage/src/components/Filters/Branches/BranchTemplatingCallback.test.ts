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

import { html } from "hybrids";
import { describe, it, expect } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type { LazyboxItem } from "@tuleap/lazybox";
import { BranchTemplatingCallback } from "./BranchTemplatingCallback";

const renderTemplate = (item: LazyboxItem): HTMLElement => {
    const doc = document.implementation.createHTMLDocument();
    const target = doc.createElement("span");
    const template = BranchTemplatingCallback(html, item);

    template(target, target);

    return target;
};

describe("BranchTemplatingCallback", () => {
    it("Given a LazyboxItem containing a Branch, then it should display its name", () => {
        const branch = { name: "walnut" };
        const branch_display = renderTemplate({
            is_disabled: false,
            value: branch,
        });

        expect(
            selectOrThrow(branch_display, "[data-test=pull-request-branch]").textContent?.trim(),
        ).toBe(branch.name);
    });

    it("Given a LazyboxItem which does not contain a user, then it should display nothing", () => {
        const branch_display = renderTemplate({
            is_disabled: false,
            value: {},
        });

        expect(branch_display.childElementCount).toBe(0);
    });
});
