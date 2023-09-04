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

import { describe, it, expect } from "vitest";
import { PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN } from "@tuleap/tlp-relative-date";
import { selectOrThrow } from "@tuleap/dom";
import { getHeaderTemplate } from "./CommentHeaderTemplate";
import { RelativeDatesHelper } from "../helpers/relative-dates-helper";

describe("CommentHeaderTemplate", () => {
    it("should display a tlp-relative-date taking into account the user preferences", () => {
        const doc = document.implementation.createHTMLDocument();
        const host = doc.createElement("div");
        const target = doc.createElement("div") as unknown as ShadowRoot;
        const render = getHeaderTemplate(
            {
                display_name: "Joe l'Asticot",
                user_url: "url/to/user_profile.html",
            },
            RelativeDatesHelper("Y-m-d H:i", PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN, "en_US"),
            "2023-03-13T16:30:00Z",
        );

        render(host, target);

        const author_info = selectOrThrow(target, "[data-test=comment-header-author]");
        expect(author_info.getAttribute("href")).toBe("url/to/user_profile.html");
        expect(author_info.textContent?.trim()).toBe("Joe l'Asticot");

        const relative_date = selectOrThrow(target, "[data-test=comment-header-date]");
        expect(relative_date.getAttribute("date")).toBe("2023-03-13T16:30:00Z");
        expect(relative_date.getAttribute("preference")).toBe("absolute");
        expect(relative_date.getAttribute("locale")).toBe("en_US");
        expect(relative_date.getAttribute("placement")).toBe("right");

        const absolute_date = relative_date.getAttribute("absolute-date");
        expect(absolute_date).toBeDefined();
        expect(relative_date.textContent?.trim()).toStrictEqual(absolute_date);
    });
});
