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

import * as tuleap_api from "@tuleap/fetch-result";
import { PullRequestCommentNewReplySaver } from "./PullRequestCommentReplySaver";
import type { ReplyCommentFormPresenter } from "./ReplyCommentFormPresenter";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";

describe("PullRequestCommentReplySaver", () => {
    it("Given a ReplyCommentFormPresenter and a global comment, then it should save it as a regular comment", () => {
        const form_presenter = {
            pull_request_id: 144,
            comment_content: "Please rebase",
            comment_author: {
                user_id: 104,
            },
        } as ReplyCommentFormPresenter;

        const postSpy = jest.spyOn(tuleap_api, "postJSON").mockImplementation();

        PullRequestCommentNewReplySaver().saveReply(
            PullRequestCommentPresenterStub.buildGlobalComment(),
            form_presenter
        );

        expect(postSpy).toHaveBeenCalledWith("/api/v1/pull_requests/144/comments", {
            content: "Please rebase",
            parent_id: 12,
            user_id: 104,
        });
    });

    it("Given a ReplyCommentFormPresenter and an inline-comment, then it should save it as an inline-comment", () => {
        const form_presenter = {
            pull_request_id: 144,
            comment_content: "Please rebase",
            comment_author: {
                user_id: 104,
            },
        } as ReplyCommentFormPresenter;

        const postSpy = jest.spyOn(tuleap_api, "postJSON").mockImplementation();

        PullRequestCommentNewReplySaver().saveReply(
            PullRequestCommentPresenterStub.buildInlineComment(),
            form_presenter
        );

        expect(postSpy).toHaveBeenCalledWith("/api/v1/pull_requests/144/inline-comments", {
            content: "Please rebase",
            parent_id: 12,
            user_id: 104,
            file_path: "README.md",
            position: "right",
            unidiff_offset: 8,
        });
    });

    it("Given a ReplyCommentFormPresenter and an file-diff comment presenter, then it should save it as an inline-comment", () => {
        const form_presenter = {
            pull_request_id: 144,
            comment_content: "Please rebase",
            comment_author: {
                user_id: 104,
            },
        } as ReplyCommentFormPresenter;

        const postSpy = jest.spyOn(tuleap_api, "postJSON").mockImplementation();

        PullRequestCommentNewReplySaver().saveReply(
            PullRequestCommentPresenterStub.buildFileDiffCommentPresenter(),
            form_presenter
        );

        expect(postSpy).toHaveBeenCalledWith("/api/v1/pull_requests/144/inline-comments", {
            content: "Please rebase",
            parent_id: 12,
            user_id: 104,
            file_path: "README.md",
            position: "right",
            unidiff_offset: 8,
        });
    });
});
