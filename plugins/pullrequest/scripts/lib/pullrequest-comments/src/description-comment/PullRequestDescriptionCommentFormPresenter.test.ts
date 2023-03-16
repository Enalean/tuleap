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
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";

describe("PullRequestDescriptionCommentFormPresenter", () => {
    let description: PullRequestDescriptionCommentPresenter;

    beforeEach(() => {
        description = {
            pull_request_id: 15,
            pull_request_raw_title: "Fix some stuff",
            raw_content: `This commit fixes bug #123`,
            content: `This commit fixes <a class="cross-reference">bug #123</a>`,
        } as PullRequestDescriptionCommentPresenter;
    });

    it("fromCurrentDescription() should build a presenter from the current description's raw_content", () => {
        expect(
            PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(description)
        ).toStrictEqual({
            pull_request_id: description.pull_request_id,
            pull_request_raw_title: description.pull_request_raw_title,
            description_content: description.raw_content,
            is_being_submitted: false,
        });
    });

    it("updateDescriptionContent() should return a new presenter containing the updated description", () => {
        const current_presenter =
            PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(description);
        expect(
            PullRequestDescriptionCommentFormPresenter.updateDescriptionContent(
                current_presenter,
                "This commit fixes bug #456"
            )
        ).toStrictEqual({
            pull_request_id: current_presenter.pull_request_id,
            pull_request_raw_title: current_presenter.pull_request_raw_title,
            description_content: "This commit fixes bug #456",
            is_being_submitted: false,
        });
    });

    it("buildSubmitted() should return a clone of the provided presenter with is_being_submitted being true", () => {
        const current_presenter =
            PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(description);
        expect(
            PullRequestDescriptionCommentFormPresenter.buildSubmitted(current_presenter)
        ).toStrictEqual({
            pull_request_id: current_presenter.pull_request_id,
            pull_request_raw_title: current_presenter.pull_request_raw_title,
            description_content: current_presenter.description_content,
            is_being_submitted: true,
        });
    });
});
