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

import { describe, expect, it, vi } from "vitest";
import { okAsync } from "neverthrow";
import * as tuleap_api from "@tuleap/fetch-result";
import { PullRequestDescriptionCommentSaver } from "./PullRequestDescriptionCommentSaver";
import { uri } from "@tuleap/fetch-result";
import { FORMAT_COMMONMARK } from "@tuleap/plugin-pullrequest-constants";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";

vi.mock("@tuleap/fetch-result");

describe("PullRequestDescriptionCommentSaver", () => {
    it("should save the new description comment", () => {
        const pull_request_id = 15;
        const patchSpy = vi
            .spyOn(tuleap_api, "patchJSON")
            .mockReturnValue(okAsync({ id: pull_request_id }));

        const current_description = {
            pull_request_id: 15,
            raw_content: "This commit fixes bug #456",
            content: `This commit fixes <a class="cross-reference">bug #456</a>`,
        } as PullRequestDescriptionCommentPresenter;
        const is_comments_markdown_mode_enabled = true;

        PullRequestDescriptionCommentSaver().saveDescriptionComment(
            PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(current_description),
            is_comments_markdown_mode_enabled,
        );

        expect(patchSpy).toHaveBeenCalledWith(uri`/api/v1/pull_requests/${pull_request_id}`, {
            description: current_description.raw_content,
            description_format: FORMAT_COMMONMARK,
        });
    });
});
