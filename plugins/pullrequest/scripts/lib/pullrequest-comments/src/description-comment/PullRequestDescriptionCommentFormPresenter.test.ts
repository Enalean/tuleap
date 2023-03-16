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
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";

describe("PullRequestDescriptionCommentFormPresenter", () => {
    it("fromCurrentDescription() should build a presenter from the current description's raw_content", () => {
        const current_description = {
            content: `This commit fixes <a class="cross-reference">bug #123</a>`,
            raw_content: `This commit fixes bug #123`,
        } as PullRequestDescriptionCommentPresenter;

        expect(
            PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(current_description)
        ).toStrictEqual({
            description_content: current_description.raw_content,
        });
    });
});
