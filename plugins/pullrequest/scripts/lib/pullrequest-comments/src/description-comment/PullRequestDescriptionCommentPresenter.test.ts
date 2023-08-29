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
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";

describe("PullRequestDescriptionCommentPresenter", () => {
    it(`fromPullRequestWithUpdatedDescription() should return a clone of the given presenter with only description and raw_description updated`, () => {
        const old_description_presenter: PullRequestDescriptionCommentPresenter = {
            pull_request_id: 15,
            project_id: 105,
            post_date: "2023-03-16T11:45:00Z",
            author: {
                id: 102,
                avatar_url: "url/to/user_avatar.png",
                user_url: "url/to/user_profile.html",
                display_name: "Joe l'Asticot",
            },
            can_user_update_description: true,
            content: '<a class="cross-reference">bug #123</a>',
            raw_content: "bug #123",
        };

        const updated_pull_request = {
            description: '<a class="cross-reference">bug #456</a>',
            raw_description: "bug #456",
        } as PullRequest;

        expect(
            PullRequestDescriptionCommentPresenter.fromPullRequestWithUpdatedDescription(
                old_description_presenter,
                updated_pull_request
            )
        ).toStrictEqual({
            ...old_description_presenter,
            raw_content: updated_pull_request.raw_description,
            content: updated_pull_request.description,
        });
    });
});
