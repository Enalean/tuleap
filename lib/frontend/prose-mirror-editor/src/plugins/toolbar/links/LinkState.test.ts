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
import { LinkState } from "./LinkState";

describe("LinkState", () => {
    it("LinkState.disabled() should return a disabled state", () => {
        expect(LinkState.disabled()).toStrictEqual({
            is_activated: false,
            is_disabled: true,
            link_title: "",
            link_href: "",
        });
    });

    it("LinkState.forLinkEdition() should return a state for a link edition", () => {
        const link = {
            href: "https://example.com",
            title: "See example",
        };

        expect(LinkState.forLinkEdition(link)).toStrictEqual({
            is_activated: true,
            is_disabled: false,
            link_title: link.title,
            link_href: link.href,
        });
    });

    it("LinkState.forLinkCreation() should return a state for a link creation", () => {
        const text_in_selection = "Some text";

        expect(LinkState.forLinkCreation(text_in_selection)).toStrictEqual({
            is_activated: false,
            is_disabled: false,
            link_title: text_in_selection,
            link_href: "",
        });
    });
});
