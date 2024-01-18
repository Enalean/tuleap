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
import { UserStub } from "../../../../tests/stubs/UserStub";
import { AuthorTemplatingCallback } from "./AuthorTemplatingCallback";

const renderTemplate = (item: LazyboxItem): HTMLElement => {
    const doc = document.implementation.createHTMLDocument();
    const target = doc.createElement("span");
    const template = AuthorTemplatingCallback(html, item);

    template(target, target);

    return target;
};

describe("AuthorTemplatingCallback", () => {
    it("Given a LazyboxItem containing a user, then it should display its name and avatar", () => {
        const author = UserStub.withIdAndName(101, "Joe l'asticot (jolasti)");
        const author_display = renderTemplate({
            is_disabled: false,
            value: author,
        });

        expect(
            selectOrThrow(author_display, "[data-test=pull-request-author]").textContent?.trim(),
        ).toBe(author.display_name);
        expect(
            selectOrThrow(author_display, "[data-test=pull-request-author-avatar]").getAttribute(
                "src",
            ),
        ).toBe(author.avatar_url);
    });

    it("Given a LazyboxItem which does not contain a user, then it should display nothing", () => {
        const author_display = renderTemplate({
            is_disabled: false,
            value: {},
        });

        expect(author_display.childElementCount).toBe(0);
    });
});
