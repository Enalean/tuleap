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

import { describe, beforeEach, it, expect } from "vitest";
import { Option } from "@tuleap/option";
import { PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN } from "@tuleap/tlp-relative-date";
import { selectOrThrow } from "@tuleap/dom";
import { getHeaderTemplate } from "./CommentHeaderTemplate";
import type { HelpRelativeDatesDisplay } from "../helpers/relative-dates-helper";
import { RelativeDatesHelper } from "../helpers/relative-dates-helper";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";

const comment_author = {
    display_name: "Joe l'Asticot",
    user_url: "url/to/user_profile.html",
};

describe("CommentHeaderTemplate", () => {
    let relative_date_helper: HelpRelativeDatesDisplay;

    beforeEach(() => {
        relative_date_helper = RelativeDatesHelper(
            "Y-m-d H:i",
            PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
            "en_US",
        );
    });

    const renderHeader = (post_date: string, last_edition_date: Option<string>): ShadowRoot => {
        const doc = document.implementation.createHTMLDocument();
        const host = doc.createElement("div");
        const target = doc.createElement("div") as unknown as ShadowRoot;
        const render = getHeaderTemplate(
            comment_author,
            relative_date_helper,
            GettextProviderStub,
            post_date,
            last_edition_date,
        );

        render(host, target);

        return target;
    };

    it("should display a tlp-relative-date taking into account the user preferences", () => {
        const comment_post_date = "2023-03-13T16:30:00Z";
        const header = renderHeader(comment_post_date, Option.nothing());

        const author_info = selectOrThrow(header, "[data-test=comment-header-author]");
        expect(author_info.getAttribute("href")).toBe(comment_author.user_url);
        expect(author_info.textContent?.trim()).toBe(comment_author.display_name);

        const post_date = selectOrThrow(
            header,
            "[data-test=comment-header-date] > tlp-relative-date",
        );
        expect(post_date.getAttribute("date")).toBe(comment_post_date);
        expect(post_date.getAttribute("preference")).toBe(
            relative_date_helper.getRelativeDatePreference(),
        );
        expect(post_date.getAttribute("locale")).toBe(relative_date_helper.getUserLocale());
        expect(post_date.getAttribute("placement")).toBe(
            relative_date_helper.getRelativeDatePlacement(),
        );

        const absolute_date = post_date.getAttribute("absolute-date");
        expect(absolute_date).toBeDefined();
        expect(post_date.textContent?.trim()).toStrictEqual(absolute_date);
    });

    it("should display the last_edition_date when there is one", () => {
        const comment_last_edition_date = "2023-03-13T17:00:00Z";
        const header = renderHeader(
            "2023-03-13T16:30:00Z",
            Option.fromValue(comment_last_edition_date),
        );

        const author_info = selectOrThrow(header, "[data-test=comment-header-author]");
        expect(author_info.getAttribute("href")).toBe(comment_author.user_url);
        expect(author_info.textContent?.trim()).toBe(comment_author.display_name);

        const post_date = selectOrThrow(
            header,
            "[data-test=comment-header-date] > tlp-relative-date",
        );
        expect(post_date).toBeDefined();

        const last_edition_date = selectOrThrow(
            header,
            "[data-test=comment-header-last-edition-date] > tlp-relative-date",
        );

        expect(last_edition_date.getAttribute("date")).toBe(comment_last_edition_date);
        expect(post_date.getAttribute("preference")).toBe(
            relative_date_helper.getRelativeDatePreference(),
        );
        expect(post_date.getAttribute("locale")).toBe(relative_date_helper.getUserLocale());
        expect(post_date.getAttribute("placement")).toBe(
            relative_date_helper.getRelativeDatePlacement(),
        );

        const absolute_date = last_edition_date.getAttribute("absolute-date");
        expect(absolute_date).toBeDefined();
        expect(last_edition_date.textContent?.trim()).toStrictEqual(absolute_date);
    });
});
