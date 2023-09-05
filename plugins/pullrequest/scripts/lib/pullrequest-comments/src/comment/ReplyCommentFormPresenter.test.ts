/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import { ReplyCommentFormPresenter } from "./ReplyCommentFormPresenter";
import type { PullRequestPresenter } from "./PullRequestPresenter";
import type { CurrentPullRequestUserPresenter } from "../types";
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { CurrentPullRequestPresenterStub } from "../../tests/stubs/CurrentPullRequestPresenterStub";

describe("ReplyCommentFormPresenter", () => {
    let comment_author: CurrentPullRequestUserPresenter, current_pull_request: PullRequestPresenter;

    beforeEach(() => {
        comment_author = CurrentPullRequestUserPresenterStub.withDefault();
        current_pull_request = CurrentPullRequestPresenterStub.withDefault();
    });

    it("should build an empty presenter with defaults", () => {
        const presenter = ReplyCommentFormPresenter.buildEmpty(
            comment_author,
            current_pull_request,
        );

        expect(presenter).toStrictEqual({
            pull_request_id: current_pull_request.pull_request_id,
            comment_author,
            comment_content: "",
            is_being_submitted: false,
            is_submittable: false,
        });
    });

    describe("updates", () => {
        let presenter: ReplyCommentFormPresenter;

        beforeEach(() => {
            presenter = ReplyCommentFormPresenter.buildEmpty(comment_author, current_pull_request);
        });

        it("should return a presenter with its content updated", () => {
            const updated_presenter = ReplyCommentFormPresenter.updateContent(
                presenter,
                "Please rebase",
            );
            expect(updated_presenter.comment_content).toBe("Please rebase");
            expect(updated_presenter.is_submittable).toBe(true);

            const another_updated_presenter = ReplyCommentFormPresenter.updateContent(
                updated_presenter,
                "",
            );
            expect(another_updated_presenter.comment_content).toBe("");
            expect(another_updated_presenter.is_submittable).toBe(false);
        });

        it("should return a presenter with the is_being_submitted property set to true", () => {
            const updated_presenter = ReplyCommentFormPresenter.updateContent(
                presenter,
                "Please rebase",
            );
            expect(updated_presenter.is_being_submitted).toBe(false);

            const submitted_presenter = ReplyCommentFormPresenter.buildSubmitted(presenter);

            expect(submitted_presenter.is_being_submitted).toBe(true);
        });

        it("should return a presenter with the is_being_submitted property set to false", () => {
            const updated_presenter = ReplyCommentFormPresenter.buildSubmitted(presenter);
            expect(updated_presenter.is_being_submitted).toBe(true);

            const submitted_presenter = ReplyCommentFormPresenter.buildNotSubmitted(presenter);
            expect(submitted_presenter.is_being_submitted).toBe(false);
        });
    });
});
