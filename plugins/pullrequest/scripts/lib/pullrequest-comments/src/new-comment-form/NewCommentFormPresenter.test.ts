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
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { NewCommentFormComponentConfigStub } from "../../tests/stubs/NewCommentFormComponentConfigStub";
import type { CurrentPullRequestUserPresenter } from "../types";
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import type { NewCommentFormComponentConfig } from "./NewCommentFormController";

describe("NewCommentFormPresenter", () => {
    let comment_author: CurrentPullRequestUserPresenter, config: NewCommentFormComponentConfig;

    beforeEach(() => {
        comment_author = CurrentPullRequestUserPresenterStub.withDefault();
        config = NewCommentFormComponentConfigStub.withCancelActionAllowed();
    });

    it("should build an empty presenter with defaults", () => {
        const presenter = NewCommentFormPresenter.buildFromAuthor(comment_author, config);

        expect(presenter).toStrictEqual({
            comment_author,
            comment_content: "",
            is_being_submitted: false,
            is_submittable: false,
            is_cancel_allowed: config.is_cancel_allowed,
        });
    });

    describe("updates", () => {
        let presenter: NewCommentFormPresenter;

        beforeEach(() => {
            presenter = NewCommentFormPresenter.buildFromAuthor(comment_author, config);
        });

        it("should return a presenter with its content updated", () => {
            const updated_presenter = NewCommentFormPresenter.updateContent(
                presenter,
                "Please rebase",
            );
            expect(updated_presenter.comment_content).toBe("Please rebase");
            expect(updated_presenter.is_submittable).toBe(true);

            const another_updated_presenter = NewCommentFormPresenter.updateContent(
                updated_presenter,
                "",
            );
            expect(another_updated_presenter.comment_content).toBe("");
            expect(another_updated_presenter.is_submittable).toBe(false);
        });

        it("should return a presenter with the is_being_submitted property set to true", () => {
            const updated_presenter = NewCommentFormPresenter.updateContent(
                presenter,
                "Please rebase",
            );
            expect(updated_presenter.is_being_submitted).toBe(false);

            const submitted_presenter = NewCommentFormPresenter.buildSubmitted(presenter);

            expect(submitted_presenter.is_being_submitted).toBe(true);
        });

        it("should return a presenter with the is_being_submitted property set to false", () => {
            const updated_presenter = NewCommentFormPresenter.buildSubmitted(presenter);
            expect(updated_presenter.is_being_submitted).toBe(true);

            const submitted_presenter = NewCommentFormPresenter.buildNotSubmitted(presenter);
            expect(submitted_presenter.is_being_submitted).toBe(false);
        });
    });
});
