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
import { selectOrThrow } from "@tuleap/dom";
import { getCommentAvatarTemplate } from "./CommentAvatarTemplate";

describe("CommentAvatarTemplate", () => {
    it("should display the avatar", () => {
        const doc = document.implementation.createHTMLDocument();
        const host = doc.createElement("div");
        const target = doc.createElement("div") as unknown as ShadowRoot;
        const render = getCommentAvatarTemplate({
            avatar_url: "url/to/avatar.png",
        });

        render(host, target);

        expect(
            selectOrThrow(target, "[data-test=comment-author-avatar-img]").getAttribute("src"),
        ).toBe("url/to/avatar.png");
    });
});
