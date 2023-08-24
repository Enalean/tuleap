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
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import { NewCommentFormComponentConfigStub } from "../../tests/stubs/NewCommentFormComponentConfigStub";

const author = { avatar_url: "url/to/user_avatar.png" };

describe("NewCommentFormPresenter", () => {
    const getBasePresenter = (): NewCommentFormPresenter =>
        NewCommentFormPresenter.buildWithUpdatedComment(
            NewCommentFormPresenter.buildFromAuthor(
                author,
                NewCommentFormComponentConfigStub.withCancelActionAllowed()
            ),
            "This is a new comment"
        );

    it.each([
        [NewCommentFormComponentConfigStub.withCancelActionAllowed()],
        [NewCommentFormComponentConfigStub.withCancelActionDisallowed()],
    ])(
        "buildFromAuthor() should build a new presenter with the current user avatar url and current config",
        (config) => {
            expect(NewCommentFormPresenter.buildFromAuthor(author, config)).toStrictEqual({
                comment: "",
                is_saving_comment: false,
                is_cancel_allowed: config.is_cancel_allowed,
                author,
            });
        }
    );

    it("buildWithUpdatedComment() should return a clone of the provided presenter containing the updated comment", () => {
        expect(
            NewCommentFormPresenter.buildWithUpdatedComment(
                getBasePresenter(),
                "This is a newer comment"
            )
        ).toStrictEqual({
            comment: "This is a newer comment",
            is_saving_comment: false,
            is_cancel_allowed: true,
            author,
        });
    });

    it("buildSavingComment() should return a clone of the provided presenter containing is_saving_comment as true", () => {
        expect(NewCommentFormPresenter.buildSavingComment(getBasePresenter())).toStrictEqual({
            comment: "This is a new comment",
            is_saving_comment: true,
            is_cancel_allowed: true,
            author,
        });
    });

    it("buildNotSavingComment() should return a clone of the provided presenter containing is_saving_comment as false", () => {
        const presenter_saving_comment = NewCommentFormPresenter.buildSavingComment(
            getBasePresenter()
        );
        expect(
            NewCommentFormPresenter.buildNotSavingComment(presenter_saving_comment)
        ).toStrictEqual({
            comment: "This is a new comment",
            is_saving_comment: false,
            is_cancel_allowed: true,
            author,
        });
    });
});
