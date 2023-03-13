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

import { describe, it, beforeEach, expect } from "vitest";
import { PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN } from "@tuleap/tlp-relative-date";
import type { HostElement } from "./PullRequestDescriptionComment";
import { RelativeDateHelperStub } from "../../tests/stubs/RelativeDateHelperStub";
import { PullRequestCommentDescriptionComponent } from "./PullRequestDescriptionComment";
import { selectOrThrow } from "@tuleap/dom";

import "@tuleap/tlp-relative-date";

describe("PullRequestDescriptionComment", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    it("should render the pull-request description comment", () => {
        const user = {
            id: 102,
            user_locale: "fr_FR",
            avatar_url: "url/to/user_avatar.png",
            user_url: "url/to/user_profile.html",
            display_name: "Joe l'Asticot",
        };

        const host = {
            current_user: {
                user_id: user.id,
                avatar_url: user.avatar_url,
                user_locale: user.user_locale,
                preferred_date_format: "Y/M/D H:m",
                preferred_relative_date_display: PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
            },
            description: {
                author: {
                    id: user.id,
                    avatar_url: user.avatar_url,
                    user_url: user.user_url,
                    display_name: user.display_name,
                },
                content: "This commit fixes an old bug.",
                post_date: "2023-03-13T15:00:00Z",
                can_user_update_description: true,
            },
            relative_date_helper: RelativeDateHelperStub,
        } as HostElement;

        const update = PullRequestCommentDescriptionComponent.content(host);
        update(host, target);

        expect(selectOrThrow(target, "[data-test=comment-author-avatar]")).toBeDefined();
        expect(selectOrThrow(target, "[data-test=comment-header]")).toBeDefined();
        expect(selectOrThrow(target, "[data-test=description-content]").textContent?.trim()).toBe(
            "This commit fixes an old bug."
        );
    });
});
