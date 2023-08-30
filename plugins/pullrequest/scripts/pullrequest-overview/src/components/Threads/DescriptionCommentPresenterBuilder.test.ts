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
import type { PullRequest, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { FORMAT_COMMONMARK } from "@tuleap/plugin-pullrequest-constants";
import { DescriptionCommentPresenterBuilder } from "./DescriptionCommentPresenterBuilder";

describe("DescriptionCommentPresenterBuilder", () => {
    it("should build the presenter from the pull-request and its author", () => {
        const pull_request = {
            id: 15,
            description: `This commit fixes <a class="cross-reference">bug #123</a>`,
            raw_description: "This commit fixes bug #123",
            creation_date: "2023-03-13T15:13:00Z",
            user_can_merge: true,
            user_can_update_title_and_description: true,
            post_processed_description: "<p>This commit fixes bug #123</p>",
            description_format: FORMAT_COMMONMARK,
        } as PullRequest;

        const author = {
            id: 102,
            user_url: "url/to/user_profile.html",
            avatar_url: "url/to/user_avatar.png",
            display_name: "Joe l'asticot",
        } as User;

        const project_id = 105;

        expect(
            DescriptionCommentPresenterBuilder.fromPullRequestAndItsAuthor(
                pull_request,
                author,
                project_id
            )
        ).toStrictEqual({
            pull_request_id: pull_request.id,
            project_id,
            author,
            content: pull_request.description,
            raw_content: pull_request.raw_description,
            post_processed_content: pull_request.post_processed_description,
            format: pull_request.description_format,
            post_date: pull_request.creation_date,
            can_user_update_description: pull_request.user_can_update_title_and_description,
        });
    });
});
