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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { SpyInstance } from "vitest";
import * as tuleap_api from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import { EditionFormPresenter } from "./EditionFormPresenter";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import { EditedCommentSaver } from "./EditedCommentSaver";

const comment_id = 110;

describe("EditedCommentSaver", () => {
    let patchSpy: SpyInstance;

    beforeEach(() => {
        patchSpy = vi.spyOn(tuleap_api, "patchJSON");
    });

    it("Given a global comment, then it should call /api/v1/pull_request_comments/{id} route", () => {
        const presenter = EditionFormPresenter.fromComment(
            PullRequestCommentPresenterStub.buildGlobalCommentWithData({ id: comment_id }),
        );

        EditedCommentSaver().saveEditedComment(presenter);

        expect(patchSpy).toHaveBeenCalledWith(
            uri`/api/v1/pull_request_comments/${presenter.comment_id}`,
            {
                content: presenter.edited_content,
            },
        );
    });

    it("Given an inline comment, then it should call /api/v1/pull_request_inline_comments/{id} route", () => {
        const presenter = EditionFormPresenter.fromComment(
            PullRequestCommentPresenterStub.buildInlineCommentWithData({ id: comment_id }),
        );

        EditedCommentSaver().saveEditedComment(presenter);

        expect(patchSpy).toHaveBeenCalledWith(
            uri`/api/v1/pull_request_inline_comments/${presenter.comment_id}`,
            {
                content: presenter.edited_content,
            },
        );
    });
});
