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
import { CommentPresenterBuilder } from "./CommentPresenterBuilder";
import type { CommentOnFile, TimelineItem } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    INLINE_COMMENT_POSITION_LEFT,
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
    FORMAT_TEXT,
} from "@tuleap/plugin-pullrequest-constants";

const pull_request_id = 15;
const user = {
    id: 102,
    user_url: "url/to/user_profile.html",
    avatar_url: "url/to/user_avatar.png",
    display_name: "Joe l'asticot",
};

describe("CommentPresenterBuilder", () => {
    let base_url: URL;

    beforeEach(() => {
        base_url = new URL("https://example.com/");
    });

    it("Given the payload of a global comment, then it should build its presenter", () => {
        const payload: TimelineItem = {
            id: 12,
            post_date: "2023-03-03T10:50:00Z",
            content: "This\nis\nawesome",
            raw_content: "This\nis\nawesome",
            post_processed_content: "This is awesome",
            format: FORMAT_TEXT,
            type: TYPE_GLOBAL_COMMENT,
            color: "deep-purple",
            user,
            parent_id: 0,
        };

        const presenter = CommentPresenterBuilder.fromPayload(payload, base_url, pull_request_id);

        expect(presenter).toStrictEqual({
            id: 12,
            user,
            content: "This<br/>is<br/>awesome",
            raw_content: "This\nis\nawesome",
            post_processed_content: "This is awesome",
            format: FORMAT_TEXT,
            type: TYPE_GLOBAL_COMMENT,
            post_date: "2023-03-03T10:50:00Z",
            parent_id: 0,
            color: "deep-purple",
        });
    });

    it("Given the payload of a comment on a file, then it should build its presenter", () => {
        const payload: CommentOnFile = {
            id: 12,
            post_date: "2023-03-03T10:50:00Z",
            content: "This\nis\nNOT\nawesome",
            raw_content: "This\nis\nNOT\nawesome",
            post_processed_content: "This is NOT awesome",
            format: FORMAT_TEXT,
            type: TYPE_INLINE_COMMENT,
            color: "deep-purple",
            user,
            parent_id: 0,
            is_outdated: true,
            file_path: "README.md",
            position: INLINE_COMMENT_POSITION_LEFT,
            unidiff_offset: 150,
        };

        const presenter = CommentPresenterBuilder.fromPayload(payload, base_url, pull_request_id);

        expect(presenter).toStrictEqual({
            id: 12,
            user,
            content: "This<br/>is<br/>NOT<br/>awesome",
            raw_content: "This\nis\nNOT\nawesome",
            post_processed_content: "This is NOT awesome",
            format: FORMAT_TEXT,
            type: TYPE_INLINE_COMMENT,
            is_outdated: true,
            post_date: "2023-03-03T10:50:00Z",
            parent_id: 0,
            color: "deep-purple",
            file: {
                file_url: "https://example.com/#/pull-requests/15/files/diff-README.md/12",
                file_path: "README.md",
                unidiff_offset: 150,
                position: INLINE_COMMENT_POSITION_LEFT,
                is_displayed: true,
            },
        });
    });
});
